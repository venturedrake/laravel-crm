<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

class FixtureProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('b', 32)),
            'app.debug' => true,
            'logging.default' => 'single',
            'logging.channels.single' => ['driver' => 'single', 'path' => base_path('storage/logs/laravel.log')],
            'app.url' => 'http://127.0.0.1:8765',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => base_path('database/database.sqlite'),
            'session.driver' => 'file', 'cache.default' => 'array', 'queue.default' => 'sync',
            'auth.providers.users.model' => User::class,
            'mary.prefix' => 'mary-',
            'laravel-crm.user_interface' => true, 'laravel-crm.teams' => false, 'laravel-crm.encrypt_db_fields' => false,
        ]);
    }

    public function boot(): void
    {
        $this->app->instance('xero', new class
        {
            public function isConnected(): bool
            {
                return false;
            }
        });
        // Fixture routes exist only in this loopback-only disposable host.
        Route::get('/health', fn () => 'ok');
        Route::middleware('web')->group(function () {
            Route::get('/fixture/start', function () {
                $user = User::firstOrCreate(['email' => 'browser@example.test'], ['name' => 'Browser', 'crm_access' => 1, 'password' => bcrypt('test')]);
                Auth::login($user);
                foreach (['currency' => 'USD', 'team' => 'related'] as $name => $value) {
                    Setting::updateOrCreate(compact('name'), compact('value'));
                }
                foreach (['Quote', 'Order', 'Invoice', 'PurchaseOrder', 'Delivery', 'Deal'] as $type) {
                    $pipeline = Pipeline::firstOrCreate(['model' => 'VentureDrake\\LaravelCrm\\Models\\'.$type], ['name' => $type]);
                    $pipeline->pipelineStages()->firstOrCreate(['name' => 'Draft'], ['order' => 0]);
                }
                app('laravel-crm.settings')->forgetCache();
                // A Project Code text field on quotes, as in issue #147. Made
                // before the quotes, so each one gets a value row for it.
                $projectCode = Field::firstOrCreate(['name' => 'Project Code'], ['type' => 'text']);
                FieldModel::firstOrCreate(['field_id' => $projectCode->id, 'model' => Quote::class]);
                $product = Product::create(['name' => 'Fractional product']);
                $order = Order::create(['currency' => 'USD']);
                $line = $order->orderProducts()->create(['product_id' => $product->id, 'quantity' => 2.5, 'price' => 10, 'amount' => 25]);
                $delivery = Delivery::create(['order_id' => $order->id]);
                $delivery->deliveryProducts()->create(['order_product_id' => $line->id, 'quantity' => 1.25]);
                $documents = [];
                foreach (self::families() as $family => [$type, $relation]) {
                    $class = 'VentureDrake\\LaravelCrm\\Models\\'.$type;
                    $attributes = ['currency' => 'USD', 'user_owner_id' => $user->id];
                    if (in_array($type, ['Quote', 'Order', 'Deal'])) {
                        $attributes['title'] = 'Browser document';
                    }
                    $document = $class::create($attributes);
                    $document->$relation()->create(['product_id' => $product->id, 'quantity' => 1.25, 'price' => 10, 'amount' => 12.5]);
                    $documents[$family] = $document->id;
                }

                // A $12.34 product taxed at 10% with a $19.99 Premium variation,
                // and per family a document holding 2.5 of it at the product price.
                $rate = TaxRate::firstOrCreate(['name' => 'GST'], ['rate' => 10]);
                $variationProduct = Product::create(['name' => 'Variation product', 'tax_rate_id' => $rate->id]);
                ProductPrice::create(['product_id' => $variationProduct->id, 'currency' => 'USD', 'unit_price' => 12.34]);
                $premium = $variationProduct->productVariations()->create(['name' => 'Premium']);
                ProductPrice::create(['product_id' => $variationProduct->id, 'product_variation_id' => $premium->id, 'currency' => 'USD', 'unit_price' => 19.99]);
                $variationDocuments = [];
                foreach (array_diff_key(self::families(), ['deal' => true]) as $family => [$type, $relation]) {
                    $class = 'VentureDrake\\LaravelCrm\\Models\\'.$type;
                    $attributes = ['currency' => 'USD', 'user_owner_id' => $user->id];
                    if (in_array($type, ['Quote', 'Order'])) {
                        $attributes['title'] = 'Browser variation document';
                    }
                    $document = $class::create($attributes);
                    $document->$relation()->create(['product_id' => $variationProduct->id, 'quantity' => 2.5, 'price' => 12.34, 'amount' => 30.85, 'tax_amount' => 3.09, 'tax_rate' => 10]);
                    $variationDocuments[$family] = $document->id;
                }

                // Issue #147: a quote with a Premium line at $19.99 and a
                // Standard line at $12.34, holding 0.125 and 1000, and a quote
                // with the Premium line alone. Their own product, so the
                // fixture routes that find a document by product never
                // return them.
                $precision = Product::create(['name' => 'Precision product', 'tax_rate_id' => $rate->id]);
                ProductPrice::create(['product_id' => $precision->id, 'currency' => 'USD', 'unit_price' => 12.34]);
                $precisionPremium = $precision->productVariations()->create(['name' => 'Premium']);
                ProductPrice::create(['product_id' => $precision->id, 'product_variation_id' => $precisionPremium->id, 'currency' => 'USD', 'unit_price' => 19.99]);
                $twoLineQuote = Quote::create(['currency' => 'USD', 'user_owner_id' => $user->id, 'title' => 'Browser two-line quote']);
                $twoLineQuote->quoteProducts()->create(['product_id' => $precision->id, 'product_variation_id' => $precisionPremium->id, 'quantity' => 0.125, 'price' => 19.99, 'amount' => 2.50, 'tax_amount' => 25, 'tax_rate' => 10, 'order' => 0]);
                $twoLineQuote->quoteProducts()->create(['product_id' => $precision->id, 'quantity' => 1000, 'price' => 12.34, 'amount' => 12340, 'tax_amount' => 123400, 'tax_rate' => 10, 'order' => 1]);
                $twoLineQuote->update(['subtotal' => 12342.50, 'tax' => 1234.25, 'total' => 13576.75]);
                $oneLineQuote = Quote::create(['currency' => 'USD', 'user_owner_id' => $user->id, 'title' => 'Browser one-line quote']);
                $oneLineQuote->quoteProducts()->create(['product_id' => $precision->id, 'product_variation_id' => $precisionPremium->id, 'quantity' => 0.125, 'price' => 19.99, 'amount' => 2.50, 'tax_amount' => 25, 'tax_rate' => 10, 'order' => 0]);
                $oneLineQuote->update(['subtotal' => 2.50, 'tax' => 0.25, 'total' => 2.75]);

                return [
                    'order' => $order->id, 'line' => $line->id, 'product' => $product->id, 'delivery' => $delivery->id, 'documents' => $documents,
                    'variationProduct' => $variationProduct->id, 'premium' => $premium->id, 'variationDocuments' => $variationDocuments,
                    'projectCode' => $projectCode->id, 'twoLineQuote' => $twoLineQuote->id, 'oneLineQuote' => $oneLineQuote->id,
                ];
            });
            Route::get('/fixture/delivery/{order}', function (Order $order) {
                return view()->file(__DIR__.'/page.blade.php', ['component' => 'crm-delivery-create', 'parameters' => ['fromModelType' => 'order', 'fromModelId' => $order->id]]);
            });
            Route::get('/fixture/delivery-edit/{id}', function ($id) {
                return view()->file(__DIR__.'/page.blade.php', ['component' => 'crm-delivery-edit', 'parameters' => ['delivery' => Delivery::findOrFail($id)]]);
            });
            Route::get('/fixture/form/{family}/{id?}', function ($family, $id = null) {
                [$type] = self::families()[$family];
                $class = 'VentureDrake\\LaravelCrm\\Models\\'.$type;
                $parameters = $id ? [lcfirst($type) => $class::findOrFail($id)] : [];

                return view()->file(__DIR__.'/page.blade.php', ['component' => 'crm-'.$family.($id ? '-edit' : '-create'), 'parameters' => $parameters]);
            });
            Route::get('/fixture/document/{family}/{product}', function ($family, $product) {
                [$type, $relation] = self::families()[$family];
                $class = 'VentureDrake\\LaravelCrm\\Models\\'.$type;
                $document = $class::whereHas($relation, fn ($query) => $query->where('product_id', $product))->latest('id')->first();

                return ['document' => $document, 'lines' => $document->$relation()->get()];
            });
            Route::get('/fixture/product-created/{name}', function ($name) {
                return ['product' => Product::where('name', $name)->exists(), 'quotes' => Quote::count()];
            });
            Route::get('/fixture/quote-result/{quote}', function (Quote $quote) {
                $lines = $quote->quoteProducts()->orderBy('order')->orderBy('id')->get();

                return [
                    'quantities' => $lines->map(fn ($line) => (float) $line->quantity),
                    'amounts' => $lines->map(fn ($line) => $line->getRawOriginal('amount')),
                    'subtotal' => $quote->getRawOriginal('subtotal'), 'tax' => $quote->getRawOriginal('tax'), 'total' => $quote->getRawOriginal('total'),
                    'projectCode' => $quote->fields()->whereHas('field', fn ($query) => $query->where('name', 'Project Code'))->value('value'),
                    'updated' => $quote->updated_at->getTimestamp(),
                ];
            });
            Route::get('/fixture/result/{order}', function (Order $order) {
                $line = $order->orderProducts()->first();

                return ['quantities' => $line->deliveryProducts()->pluck('quantity'), 'remainder' => $line->deliveryRemainder()];
            });
        });
    }

    public static function families(): array
    {
        return [
            'quote' => ['Quote', 'quoteProducts'],
            'order' => ['Order', 'orderProducts'],
            'invoice' => ['Invoice', 'invoiceLines'],
            'purchase-order' => ['PurchaseOrder', 'purchaseOrderLines'],
            'deal' => ['Deal', 'dealProducts'],
        ];
    }
}
