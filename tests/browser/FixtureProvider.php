<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
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

                return ['order' => $order->id, 'line' => $line->id, 'product' => $product->id, 'delivery' => $delivery->id, 'documents' => $documents];
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
