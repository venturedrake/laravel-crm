<?php

/*
 * A quote saved from the form must pass its own amount checks.
 *
 * The form priced a line in float dollars and the CheckAmount helpers in
 * integer cents, so a line of $333.33 x 2.5 saved as 833.32 and the show page
 * then flagged the quote as broken and hid Send/Accept.
 */

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteCreate;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount;
use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal;
use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total;

class RoundingQuoteCreate extends QuoteCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'create crm quotes']);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    app('laravel-crm.settings')->forgetCache();

    Pipeline::create(['external_id' => Str::uuid()->toString(), 'name' => 'Quote Pipeline', 'model' => Quote::class])
        ->pipelineStages()->create(['external_id' => Str::uuid()->toString(), 'name' => 'Draft', 'order' => 0]);
});

test('a quote saved with half-cent lines passes its amount checks', function () {
    $productA = Product::create(['name' => 'A']);
    $productB = Product::create(['name' => 'B']);
    $productC = Product::create(['name' => 'C']);

    // A blank form starts with one row.
    $lines = Livewire::test(ModelProducts::class)->call('add')->call('add');

    foreach ([[0, $productA, '333.33', 2.5], [1, $productB, '9.99', 3.5], [2, $productC, '9.99', 0.5]] as [$index, $product, $price, $quantity]) {
        $lines->set("products.$index.id", $product->id);
        $lines->set("products.$index.unit_price", $price);
        $lines->set("products.$index.quantity", $quantity);
    }

    Livewire::test(RoundingQuoteCreate::class)
        ->set('person_name', 'Jane Buyer')
        ->set('title', 'Rounding quote')
        ->call('updateProducts', $lines->get('products'), $lines->get('sub_total'), $lines->get('tax'), $lines->get('total'))
        ->call('save')
        ->assertHasNoErrors();

    $quote = Quote::with('quoteProducts')->sole();

    expect($quote->quoteProducts->pluck('amount')->all())->toBe([83333, 3497, 500])
        ->and($quote->subtotal)->toBe(87330)
        ->and(subTotal($quote))->toBeTrue()
        ->and(total($quote))->toBeTrue();

    foreach ($quote->quoteProducts as $quoteProduct) {
        expect(lineAmount($quoteProduct))->toBeTrue();
    }
});
