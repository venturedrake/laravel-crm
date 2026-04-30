<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Tests\TestCase;

/**
 * Combined coverage for the three line-item models — they share an identical
 * shape (price/amount mutators ×100, belongsTo product/parent, soft deletes).
 */
class LineItemModelsTest extends TestCase
{
    public function test_quote_product_table_uuid_relations_and_money(): void
    {
        $line = QuoteProduct::create([
            'quote_id' => 1,
            'product_id' => 1,
            'quantity' => 2,
            'price' => 12.50,
            'amount' => 25.00,
        ]);

        $this->assertSame('crm_quote_products', $line->getTable());
        $this->assertTrue(Str::isUuid($line->external_id));
        $this->assertSame(1250, (int) $line->fresh()->getRawOriginal('price'));
        $this->assertSame(2500, (int) $line->fresh()->getRawOriginal('amount'));
        $this->assertInstanceOf(BelongsTo::class, $line->quote());
        $this->assertInstanceOf(BelongsTo::class, $line->product());

        $line->delete();
        $this->assertSoftDeleted('crm_quote_products', ['id' => $line->id]);
    }

    public function test_order_product_table_uuid_relations_and_money(): void
    {
        $line = OrderProduct::create([
            'order_id' => 1,
            'product_id' => 1,
            'quantity' => 3,
            'price' => 5.25,
            'amount' => 15.75,
        ]);

        $this->assertSame('crm_order_products', $line->getTable());
        $this->assertTrue(Str::isUuid($line->external_id));
        $this->assertSame(525, (int) $line->fresh()->getRawOriginal('price'));
        $this->assertSame(1575, (int) $line->fresh()->getRawOriginal('amount'));
        $this->assertInstanceOf(BelongsTo::class, $line->order());
        $this->assertInstanceOf(BelongsTo::class, $line->product());
    }

    public function test_invoice_line_table_uuid_relations_and_money(): void
    {
        $line = InvoiceLine::create([
            'invoice_id' => 1,
            'product_id' => 1,
            'quantity' => 1,
            'price' => 99.99,
            'tax_amount' => 10.00,
            'amount' => 99.99,
        ]);

        $this->assertSame('crm_invoice_lines', $line->getTable());
        $this->assertTrue(Str::isUuid($line->external_id));
        $this->assertSame(9999, (int) $line->fresh()->getRawOriginal('price'));
        $this->assertSame(1000, (int) $line->fresh()->getRawOriginal('tax_amount'));
        $this->assertSame(9999, (int) $line->fresh()->getRawOriginal('amount'));
        $this->assertInstanceOf(BelongsTo::class, $line->invoice());
        $this->assertInstanceOf(BelongsTo::class, $line->product());
    }
}
