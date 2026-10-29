<?php

/*
 * config('laravel-crm.deliveries') hides "Delivered on" and relabels
 * delivery_expected across the delivery screens and PDFs. Both default to
 * today's screens. A hidden delivered_on is skipped on save, so a stored value
 * survives an edit.
 */

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryCreate;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryEdit;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryShow;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\DeliveryOptions;
use VentureDrake\LaravelCrm\Support\PdfSampleData;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

beforeEach(function () {
    $this->actingAsUser();

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);

    // HasDeliveryCommon::mountCommon() looks its pipeline up by Invoice.
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

    $this->order = Order::create(['currency' => 'USD']);
    $this->delivery = Delivery::create([
        'order_id' => $this->order->id,
        'delivery_expected' => '2026-11-02',
        'delivered_on' => '2026-11-05',
    ]);
});

/**
 * Everything the delivery PDF blades read, as TemplatePreviewController
 * assembles it.
 */
function deliveryOptionsPdfData(Delivery $delivery, Order $order): array
{
    return [
        'delivery' => $delivery,
        'order' => $order,
        'dateFormat' => 'M j, Y',
        'taxName' => 'Tax',
        'contactDetails' => null,
        'paymentInstructions' => null,
        'fromName' => 'Sample Organization',
        'logo' => null,
        'email' => null,
        'phone' => null,
        'address' => PdfSampleData::address(),
        'organization_address' => PdfSampleData::address(),
    ];
}

function deliveryPdfHtml(Delivery $delivery, Order $order, string $slug): string
{
    return View::make(PdfTemplateRegistry::viewFor('delivery', $slug), deliveryOptionsPdfData($delivery, $order))->render();
}

dataset('delivery pdf slugs', ['modern', 'classic', 'bold', 'compact', 'professional']);

// Defaults ---------------------------------------------------------------

test('by default every delivery screen shows delivered on and the delivery expected label', function () {
    Livewire::test(DeliveryEdit::class, ['delivery' => $this->delivery])
        ->assertSeeHtml('wire:model="delivered_on"')
        ->assertSee('Delivery expected')
        ->assertSee('Delivered on');

    Livewire::test(DeliveryShow::class, ['delivery' => $this->delivery])
        ->assertSee('Delivery Expected')
        ->assertSee('Delivered On');

    Livewire::test(DeliveryIndex::class)
        ->assertSee('Delivery Expected')
        ->assertSee('Delivered On');

    Livewire::test(DeliveryRelatedIndex::class, ['model' => $this->order])
        ->assertSee('Delivery Expected')
        ->assertSee('Delivered On');
});

test('by default the delivery PDFs keep their Delivery Date heading', function (string $slug) {
    expect(deliveryPdfHtml($this->delivery, $this->order, $slug))
        ->toContain('Delivery Date')
        ->not->toContain('Delivery Expected');
})->with('delivery pdf slugs');

test('by default saving a delivery writes delivered on', function () {
    Livewire::test(DeliveryEdit::class, ['delivery' => $this->delivery])
        ->set('delivered_on', '2026-11-09')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->delivery->fresh()->delivered_on->format('Y-m-d'))->toBe('2026-11-09');
});

// delivered_on = false ------------------------------------------------------

test('with delivered_on off no delivery screen shows it', function () {
    config(['laravel-crm.deliveries.delivered_on' => false]);

    Livewire::test(DeliveryEdit::class, ['delivery' => $this->delivery])
        ->assertDontSeeHtml('wire:model="delivered_on"')
        ->assertDontSee('Delivered on')
        ->assertSee('Delivery expected');

    Livewire::test(DeliveryShow::class, ['delivery' => $this->delivery])
        ->assertDontSee('Delivered On')
        ->assertDontSee('2026-11-05')
        ->assertSee('Delivery Expected');

    Livewire::test(DeliveryIndex::class)
        ->assertDontSee('Delivered On')
        ->assertDontSee('2026-11-05')
        ->assertSee('2026-11-02');

    Livewire::test(DeliveryRelatedIndex::class, ['model' => $this->order])
        ->assertDontSee('Delivered On')
        ->assertDontSee('2026-11-05');
});

test('with delivered_on off an edit leaves the stored value alone', function () {
    config(['laravel-crm.deliveries.delivered_on' => false]);

    Livewire::test(DeliveryEdit::class, ['delivery' => $this->delivery])
        // Even a value pushed at the hidden property is not written.
        ->set('delivered_on', null)
        ->set('delivery_expected', '2026-11-03')
        ->call('save')
        ->assertHasNoErrors();

    $delivery = $this->delivery->fresh();

    expect($delivery->delivered_on->format('Y-m-d'))->toBe('2026-11-05')
        ->and($delivery->delivery_expected->format('Y-m-d'))->toBe('2026-11-03');
});

test('with delivered_on off a new delivery does not write it', function () {
    config(['laravel-crm.deliveries.delivered_on' => false]);

    Livewire::test(DeliveryCreate::class, ['fromModelType' => 'order', 'fromModelId' => $this->order->id])
        ->set('products', [])
        ->set('delivered_on', '2026-12-01')
        ->call('save')
        ->assertHasNoErrors();

    expect(Delivery::latest('id')->first()->delivered_on)->toBeNull();
});

// delivery_expected_label ---------------------------------------------------

test('delivery_expected_label relabels the form, show page, index and related index', function () {
    config(['laravel-crm.deliveries.delivery_expected_label' => 'delivery_date']);

    Livewire::test(DeliveryEdit::class, ['delivery' => $this->delivery])
        ->assertSee('Delivery date')
        ->assertDontSee('Delivery expected');

    Livewire::test(DeliveryShow::class, ['delivery' => $this->delivery])
        ->assertSee('Delivery Date')
        ->assertDontSee('Delivery Expected');

    Livewire::test(DeliveryIndex::class)
        ->assertSee('Delivery Date')
        ->assertDontSee('Delivery Expected');

    Livewire::test(DeliveryRelatedIndex::class, ['model' => $this->order])
        ->assertSee('Delivery Date')
        ->assertDontSee('Delivery Expected');
});

test('delivery_expected_label = delivery_date labels the PDFs Delivery Date', function (string $slug) {
    config(['laravel-crm.deliveries.delivery_expected_label' => 'delivery_date']);

    expect(deliveryPdfHtml($this->delivery, $this->order, $slug))->toContain('Delivery Date');
})->with('delivery pdf slugs');

test('a custom delivery_expected_label is used on the PDFs too', function (string $slug) {
    config(['laravel-crm.deliveries.delivery_expected_label' => 'due_date']);

    expect(deliveryPdfHtml($this->delivery, $this->order, $slug))
        ->toContain('Due Date')
        ->not->toContain('Delivery Date');
})->with('delivery pdf slugs');

test('an unknown delivery_expected_label falls back to delivery expected', function (string $slug) {
    config(['laravel-crm.deliveries.delivery_expected_label' => 'no_such_label_key']);

    expect(DeliveryOptions::expectedLabelKey())->toBe('delivery_expected');

    Livewire::test(DeliveryShow::class, ['delivery' => $this->delivery])
        ->assertSee('Delivery Expected')
        ->assertDontSee('no_such_label_key');

    expect(deliveryPdfHtml($this->delivery, $this->order, $slug))
        ->toContain('Delivery Date')
        ->not->toContain('no_such_label_key');
})->with('delivery pdf slugs');

test('the delivery label keys exist in every shipped locale', function (string $locale) {
    foreach (['delivery_expected', 'delivery_date'] as $key) {
        expect(Lang::has('laravel-crm::lang.'.$key, $locale, false))->toBeTrue("{$key} missing from {$locale}");
    }
})->with(['en', 'en_au', 'en_gb', 'fa']);

test('the delivered on date stays in the API-facing model', function () {
    config(['laravel-crm.deliveries.delivered_on' => false]);

    expect($this->delivery->fresh()->delivered_on->format('Y-m-d'))->toBe('2026-11-05')
        ->and($this->delivery->getCasts())->toHaveKey('delivered_on');
});
