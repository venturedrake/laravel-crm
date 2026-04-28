<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_order_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_orders', (new Order)->getTable());
    }

    public function test_creating_an_order_assigns_uuid_external_id(): void
    {
        $order = Order::create(['title' => 'O']);
        $this->assertTrue(Str::isUuid($order->external_id));
    }

    public function test_creating_an_order_auto_increments_number_starting_from_1000(): void
    {
        $first = Order::create(['title' => 'A']);
        $second = Order::create(['title' => 'B']);

        $this->assertSame(1000, $first->number);
        $this->assertSame(1001, $second->number);
    }

    public function test_order_id_is_built_from_prefix_plus_number(): void
    {
        app('laravel-crm.settings')->set('order_prefix', 'O');

        $order = Order::create(['title' => 'X']);

        $this->assertSame('O', $order->prefix);
        $this->assertSame('O1000', $order->order_id);
    }

    public function test_set_money_attributes_multiply_by_one_hundred(): void
    {
        $order = Order::create([
            'title' => 'Money',
            'subtotal' => 50,
            'discount' => 5,
            'tax' => 4.50,
            'adjustments' => 0,
            'total' => 49.50,
        ]);

        $fresh = $order->fresh();
        $this->assertSame(5000, (int) $fresh->getRawOriginal('subtotal'));
        $this->assertSame(500, (int) $fresh->getRawOriginal('discount'));
        $this->assertSame(450, (int) $fresh->getRawOriginal('tax'));
        $this->assertSame(0, (int) $fresh->getRawOriginal('adjustments'));
        $this->assertSame(4950, (int) $fresh->getRawOriginal('total'));
    }

    public function test_order_relationships_are_defined(): void
    {
        $order = new Order;

        $this->assertInstanceOf(BelongsTo::class, $order->person());
        $this->assertInstanceOf(BelongsTo::class, $order->organization());
        $this->assertInstanceOf(BelongsTo::class, $order->deal());
        $this->assertInstanceOf(BelongsTo::class, $order->quote());
        $this->assertInstanceOf(HasMany::class, $order->orderProducts());
        $this->assertInstanceOf(HasMany::class, $order->invoices());
        $this->assertInstanceOf(HasMany::class, $order->deliveries());
        $this->assertInstanceOf(HasMany::class, $order->purchaseOrders());
        $this->assertInstanceOf(MorphToMany::class, $order->labels());
    }

    public function test_order_uses_soft_deletes(): void
    {
        $order = Order::create(['title' => 'Bin me']);
        $order->delete();
        $this->assertSoftDeleted('crm_orders', ['id' => $order->id]);
    }

    public function test_order_is_auditable(): void
    {
        $this->assertInstanceOf(Auditable::class, new Order);
    }

    public function test_order_belongs_to_quote(): void
    {
        $quote = Quote::create(['title' => 'Q']);
        $order = Order::create(['title' => 'From Q', 'quote_id' => $quote->id]);

        $this->assertTrue($order->quote->is($quote));
    }
}

