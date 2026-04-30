<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Services;

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Tests\TestCase;

class OrderServiceTest extends TestCase
{
    private function service(): OrderService
    {
        return app(OrderService::class);
    }

    private function request(array $attributes): Request
    {
        return new Request($attributes);
    }

    public function test_service_creates_an_order_with_minimum_data(): void
    {
        $order = $this->service()->create($this->request([
            'description' => 'd',
            'currency' => 'USD',
            'sub_total' => 200,
            'total' => 200,
        ]));

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('USD', $order->currency);
        $this->assertSame(20000, (int) $order->fresh()->getRawOriginal('subtotal'));
    }

    public function test_service_attaches_person_org_and_quote(): void
    {
        $person = Person::create(['first_name' => 'Bob']);
        $org = Organization::create(['name' => 'Acme']);
        $quote = Quote::create(['title' => 'Source quote']);

        $order = $this->service()->create($this->request([
            'currency' => 'USD',
            'quote_id' => $quote->id,
        ]), $person, $org);

        $this->assertSame($person->id, $order->person_id);
        $this->assertSame($org->id, $order->organization_id);
        $this->assertSame($quote->id, $order->quote_id);
    }

    public function test_service_updates_an_order(): void
    {
        $order = Order::create(['description' => 'Old', 'currency' => 'USD']);

        $this->service()->update($this->request([
            'description' => 'New',
            'reference' => 'R-1',
            'currency' => 'EUR',
            'sub_total' => 5,
            'total' => 5,
        ]), $order);

        $fresh = $order->fresh();
        $this->assertSame('New', $fresh->description);
        $this->assertSame('R-1', $fresh->reference);
        $this->assertSame('EUR', $fresh->currency);
    }
}
