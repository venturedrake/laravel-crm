<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Tests\TestCase;

class InvoiceTest extends TestCase
{
    public function test_invoice_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_invoices', (new Invoice)->getTable());
    }

    public function test_creating_an_invoice_assigns_uuid(): void
    {
        $invoice = Invoice::create([]);
        $this->assertTrue(Str::isUuid($invoice->external_id));
    }

    public function test_invoice_observer_increments_number_and_builds_invoice_id(): void
    {
        app('laravel-crm.settings')->set('invoice_prefix', 'INV');

        $first = Invoice::create([]);
        $second = Invoice::create([]);

        $this->assertSame(1000, (int) $first->number);
        $this->assertSame(1001, (int) $second->number);
        $this->assertSame('INV', $first->prefix);
        $this->assertSame('INV1000', $first->invoice_id);
        $this->assertSame('INV1001', $second->invoice_id);
    }

    public function test_set_money_attributes_multiply_by_one_hundred(): void
    {
        $invoice = Invoice::create([
            'subtotal' => 200,
            'tax' => 20,
            'total' => 220,
            'amount_due' => 220,
            'amount_paid' => 50,
        ]);

        $fresh = $invoice->fresh();
        $this->assertSame(20000, (int) $fresh->getRawOriginal('subtotal'));
        $this->assertSame(2000, (int) $fresh->getRawOriginal('tax'));
        $this->assertSame(22000, (int) $fresh->getRawOriginal('total'));
        $this->assertSame(22000, (int) $fresh->getRawOriginal('amount_due'));
        $this->assertSame(5000, (int) $fresh->getRawOriginal('amount_paid'));
    }

    public function test_amount_due_accessor_returns_raw_stored_value(): void
    {
        // setAmountDueAttribute multiplies by 100, so 150 → 15000 raw.
        // The accessor returns the stored value directly (not divided),
        // so callers receive the cents amount.
        $invoice = Invoice::create(['amount_due' => 150]);

        $this->assertSame(15000, (int) $invoice->fresh()->amount_due);
    }

    public function test_invoice_relationships_are_defined(): void
    {
        $invoice = new Invoice;

        $this->assertInstanceOf(BelongsTo::class, $invoice->order());
        $this->assertInstanceOf(BelongsTo::class, $invoice->person());
        $this->assertInstanceOf(BelongsTo::class, $invoice->organization());
        $this->assertInstanceOf(HasMany::class, $invoice->invoiceLines());
        $this->assertInstanceOf(MorphToMany::class, $invoice->labels());
    }

    public function test_invoice_belongs_to_order(): void
    {
        $order = Order::create(['title' => 'O1']);
        $invoice = Invoice::create(['order_id' => $order->id]);

        $this->assertTrue($invoice->order->is($order));
    }

    public function test_invoice_uses_soft_deletes(): void
    {
        $invoice = Invoice::create([]);
        $invoice->delete();
        $this->assertSoftDeleted('crm_invoices', ['id' => $invoice->id]);
    }

    public function test_invoice_is_auditable(): void
    {
        $this->assertInstanceOf(Auditable::class, new Invoice);
    }
}
