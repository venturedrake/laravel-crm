<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Services;

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    private function service(): InvoiceService
    {
        return app(InvoiceService::class);
    }

    private function request(array $attributes): Request
    {
        return new Request($attributes);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    public function test_service_creates_an_invoice_with_minimum_data(): void
    {
        $invoice = $this->service()->create($this->request([
            'reference' => 'INV-1',
            'currency' => 'USD',
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-31',
            'sub_total' => 100,
            'tax' => 10,
            'total' => 110,
        ]));

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame('INV-1', $invoice->reference);
        $this->assertSame(11000, (int) $invoice->fresh()->getRawOriginal('total'));
    }

    public function test_service_attaches_person_org_and_order(): void
    {
        $person = Person::create(['first_name' => 'C']);
        $org = Organization::create(['name' => 'Acme']);
        $order = Order::create(['description' => 'Order']);

        $invoice = $this->service()->create($this->request([
            'currency' => 'USD',
            'order_id' => $order->id,
        ]), $person, $org);

        $this->assertSame($person->id, $invoice->person_id);
        $this->assertSame($org->id, $invoice->organization_id);
        $this->assertSame($order->id, $invoice->order_id);
    }

    public function test_service_updates_an_invoice(): void
    {
        $invoice = Invoice::create(['currency' => 'USD']);

        $this->service()->update($this->request([
            'reference' => 'NEW',
            'currency' => 'GBP',
            'sub_total' => 1,
            'tax' => 0,
            'total' => 1,
        ]), $invoice);

        $fresh = $invoice->fresh();
        $this->assertSame('NEW', $fresh->reference);
        $this->assertSame('GBP', $fresh->currency);
    }
}
