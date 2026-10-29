<?php

/*
 * The comments field on a product line.
 *
 * The lines live in the crm-model-products child, and the parent form only
 * sees them through the `model-products-updated` event the child dispatches
 * after a round trip. The field was bound with `wire:model.blur`, which on
 * Livewire 4 only updates the browser copy and sends nothing - so the child
 * never heard about the comment, never dispatched it, and the parent saved
 * the line without it.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceEdit;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Product;

class CommentsInvoiceEdit extends InvoiceEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

function invoiceWithOneLine(): Invoice
{
    $product = Product::create(['name' => 'A']);

    $invoice = Invoice::create([
        'currency' => 'USD',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
    ]);

    $invoice->invoiceLines()->create([
        'product_id' => $product->id, 'quantity' => 1, 'price' => 4500,
        'tax_rate' => 10, 'tax_amount' => 45000, 'amount' => 4500,
    ]);

    return $invoice;
}

test('the comments field sends its value to the server', function () {
    $this->actingAsUser();

    $html = Livewire::test(ModelProducts::class, ['model' => invoiceWithOneLine(), 'creating' => 'Invoice'])->html();

    expect($html)->toContain('wire:model.live.debounce.500ms="products.0.comments"')
        ->and($html)->not->toContain('wire:model.blur="products.0.comments"');
});

test('a comment is passed up to the parent form', function () {
    $this->actingAsUser();

    Livewire::test(ModelProducts::class, ['model' => invoiceWithOneLine(), 'creating' => 'Invoice'])
        ->set('products.0.comments', 'Deliver to the side gate')
        ->assertDispatched('model-products-updated', fn ($event, $params) => ($params['products'][0]['comments'] ?? null) === 'Deliver to the side gate');
});

test('editing an invoice saves the comment on an existing and a new line', function () {
    $this->actingAsUser();

    $invoice = invoiceWithOneLine();
    $line = $invoice->invoiceLines()->first();
    $newProduct = Product::create(['name' => 'B']);

    Livewire::test(CommentsInvoiceEdit::class, ['invoice' => $invoice])
        ->set('organization_name', 'Comments Co')
        ->call('updateProducts', [
            [
                'invoice_line_id' => $line->id, 'id' => $line->product_id, 'quantity' => 1,
                'unit_price' => 45, 'amount' => 45, 'comments' => 'Existing line note',
            ],
            [
                'id' => $newProduct->id, 'quantity' => 2,
                'unit_price' => 10, 'amount' => 20, 'comments' => 'New line note',
            ],
        ], 65, 0, 65)
        ->call('save')
        ->assertHasNoErrors();

    expect($invoice->invoiceLines()->orderBy('order')->pluck('comments')->all())
        ->toBe(['Existing line note', 'New line note']);
});
