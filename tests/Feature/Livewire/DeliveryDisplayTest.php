<?php

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryShow;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Setting;

beforeEach(function () {
    $this->actingAsUser();

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);

    foreach ([Invoice::class, Delivery::class] as $model) {
        $pipeline = Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => class_basename($model).' Pipeline',
            'model' => $model,
        ]);
        $pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'Draft',
            'order' => 0,
        ]);
    }

    AddressType::firstOrCreate(['name' => 'Shipping']);
});

function deliveryDisplayXPath(string $html): DOMXPath
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

    return new DOMXPath($document);
}

function assertDeliveryDisplayText(DOMXPath $xpath, string $query, string $expected): void
{
    $nodes = $xpath->query($query);
    expect($nodes->length)->toBe(1);
    expect(trim($nodes->item(0)->textContent))->toBe($expected);
}

function assertDeliveryDisplay(Delivery $delivery, string $reference, string $contact, string $title): void
{
    $index = deliveryDisplayXPath(Livewire::test(DeliveryIndex::class)->assertOk()->html());

    // Resolve the cell by its rendered column label, not other page content.
    foreach (['Reference' => $reference, 'Contact' => $contact] as $label => $expected) {
        assertDeliveryDisplayText($index,
            '//table/tbody/tr/td[position() = count(//table/thead/tr/th[normalize-space(.)="'.$label.'"]/preceding-sibling::th) + 1]',
            $expected);
    }

    $show = deliveryDisplayXPath(Livewire::test(DeliveryShow::class, ['delivery' => $delivery])->assertOk()->html());
    assertDeliveryDisplayText($show, '//strong[normalize-space(.)="Reference"]/following-sibling::span', $reference);
    assertDeliveryDisplayText($show, '(//div[contains(@class, "font-extrabold")]/span)[1]', $title);
}

test('delivery list and detail show the order reference and person contact', function (bool $deleted) {
    $person = Person::create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
    $order = Order::create([
        'currency' => 'USD',
        'total' => 1234.56,
        'person_id' => $person->id,
        'reference' => 'ORDER-REFERENCE-140',
    ]);
    $delivery = Delivery::create(['order_id' => $order->id]);

    if ($deleted) {
        $order->delete();
    }

    assertDeliveryDisplay($delivery->fresh(), 'ORDER-REFERENCE-140', 'Grace Hopper', '$1,234.56 - Grace Hopper');
})->with(['active order' => false, 'soft-deleted order' => true]);

test('delivery list and detail leave an absent order reference blank', function (?string $reference) {
    $order = Order::create(['currency' => 'USD', 'total' => 1234.56, 'reference' => $reference]);
    $delivery = Delivery::create(['order_id' => $order->id]);

    assertDeliveryDisplay($delivery->fresh(), '', '', '$1,234.56 -');
})->with(['null reference' => null, 'empty reference' => '']);

test('delivery list and detail render without an order', function () {
    assertDeliveryDisplay(Delivery::create()->fresh(), '', '', '');
});
