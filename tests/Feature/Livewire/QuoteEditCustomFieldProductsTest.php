<?php

/*
 * Issue #147: on a two-line quote, a Project Code change followed by quick
 * quantity typing saved the custom field with the old quantities and totals.
 *
 * In the browser, a response to a request sent before the typing put the old
 * rows back into the product editor (resources/js/product-form-submit.js now
 * keeps what was typed after a request left). Here the server's half is held
 * to its contract: the custom field and a stale preview event reach the form
 * before Save, and Save still stores the rows the editor prepared from what
 * was typed last, with totals worked out from them.
 */

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteEdit;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\TaxRate;

class CustomFieldProductsQuoteEdit extends QuoteEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    $pipeline = Pipeline::create(['external_id' => Str::uuid()->toString(), 'name' => 'Quote Pipeline', 'model' => Quote::class]);
    $pipeline->pipelineStages()->create(['external_id' => Str::uuid()->toString(), 'name' => 'Draft', 'order' => 0]);

    // Premium at $19.99 and Standard at $12.34, GST 10%.
    $rate = TaxRate::create(['name' => 'GST', 'rate' => 10]);
    $this->product = Product::create(['name' => 'Precision product', 'tax_rate_id' => $rate->id]);
    ProductPrice::create(['product_id' => $this->product->id, 'currency' => 'USD', 'unit_price' => 12.34]);
    $this->premium = $this->product->productVariations()->create(['name' => 'Premium']);
    ProductPrice::create(['product_id' => $this->product->id, 'product_variation_id' => $this->premium->id, 'currency' => 'USD', 'unit_price' => 19.99]);

    // Made before any quote, so each quote gets a value row for it.
    $this->projectCode = Field::create(['external_id' => Str::uuid()->toString(), 'name' => 'Project Code', 'type' => 'text']);
    FieldModel::create(['external_id' => Str::uuid()->toString(), 'field_id' => $this->projectCode->id, 'model' => Quote::class]);
});

/**
 * A quote holding 0.125 Premium and, unless $oneLine, 1000 Standard.
 */
function projectCodeQuote($test, bool $oneLine = false): Quote
{
    $quote = Quote::create([
        'title' => 'Precision quote', 'currency' => 'USD',
        'organization_id' => Organization::create(['name' => 'Customer Co'])->id,
    ]);
    $quote->quoteProducts()->create([
        'product_id' => $test->product->id, 'product_variation_id' => $test->premium->id,
        'quantity' => 0.125, 'price' => 19.99, 'amount' => 2.50, 'tax_amount' => 25, 'tax_rate' => 10, 'order' => 0,
    ]);
    if (! $oneLine) {
        $quote->quoteProducts()->create([
            'product_id' => $test->product->id,
            'quantity' => 1000, 'price' => 12.34, 'amount' => 12340, 'tax_amount' => 123400, 'tax_rate' => 10, 'order' => 1,
        ]);
    }

    return $quote->fresh();
}

/**
 * Save the quote's form as the browser does: the editor prepares the rows
 * with the quantities typed last, and the form saves what it prepared. Before
 * that, the form may get a Project Code and a preview event still carrying
 * the rows as they were.
 *
 * @param  array<int, string>  $quantities
 */
function saveQuoteForm(Quote $quote, array $quantities, $test, ?string $projectCode = null): void
{
    $editor = Livewire::test(ModelProducts::class, ['model' => $quote, 'currency' => 'USD']);
    $stale = $editor->get('products');
    $form = Livewire::test(CustomFieldProductsQuoteEdit::class, ['quote' => $quote]);

    if ($projectCode !== null) {
        $form->set("fields.{$test->projectCode->id}", $projectCode)
            ->call('updateProducts', $stale, $editor->get('sub_total'), $editor->get('tax'), $editor->get('total'));
    }

    $rows = $stale;
    foreach ($quantities as $index => $quantity) {
        $rows[$index]['quantity'] = $quantity;
    }
    $prepared = $editor->call('prepareForSave', $rows)->effects['returns'][0];

    $form->call('saveWithProducts', $prepared)->assertHasNoErrors();
}

function expectQuoteSaved(Quote $quote, array $quantities, array $amounts, array $totals): void
{
    $quote = $quote->fresh();
    $lines = $quote->quoteProducts()->orderBy('order')->orderBy('id')->get();

    expect($lines->map(fn ($line) => (float) $line->quantity)->all())->toBe($quantities)
        ->and($lines->map(fn ($line) => (int) $line->getRawOriginal('amount'))->all())->toBe($amounts)
        ->and([(int) $quote->getRawOriginal('subtotal'), (int) $quote->getRawOriginal('tax'), (int) $quote->getRawOriginal('total')])->toBe($totals);
}

function savedProjectCode(Quote $quote, $test): ?string
{
    return $quote->fields()->where('field_id', $test->projectCode->id)->value('value');
}

test('a project code sent with a stale preview saves with the quantities typed last', function (array $typed, array $quantities, array $amounts, array $totals) {
    $quote = projectCodeQuote($this);

    saveQuoteForm($quote, $typed, $this, 'RETEST-1611');

    expectQuoteSaved($quote, $quantities, $amounts, $totals);
    expect(savedProjectCode($quote, $this))->toBe('RETEST-1611');
})->with([
    // 2.5 x 19.99 = 49.98 and 3 x 12.34 = 37.02; tax 5.00 + 3.70.
    'first reproduction' => [['2.5', '3'], [2.5, 3.0], [4998, 3702], [8700, 870, 9570]],
    // 1.25 x 19.99 = 24.99 and 2 x 12.34 = 24.68; tax 2.50 + 2.47.
    'second reproduction' => [['1.25', '2'], [1.25, 2.0], [2499, 2468], [4967, 497, 5464]],
]);

test('quantities alone on a two-line quote save as typed', function () {
    $quote = projectCodeQuote($this);

    saveQuoteForm($quote, ['2.5', '3'], $this);

    expectQuoteSaved($quote, [2.5, 3.0], [4998, 3702], [8700, 870, 9570]);
    expect(savedProjectCode($quote, $this))->toBeNull();
});

test('a project code and a quantity on a one-line quote save together', function () {
    $quote = projectCodeQuote($this, oneLine: true);

    saveQuoteForm($quote, ['7.5'], $this, 'RETEST-1611');

    // 7.5 x 19.99 = 149.93 (149.925, rounded half up), plus 10% tax.
    expectQuoteSaved($quote, [7.5], [14993], [14993, 1499, 16492]);
    expect(savedProjectCode($quote, $this))->toBe('RETEST-1611');
});
