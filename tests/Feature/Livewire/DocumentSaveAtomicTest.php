<?php

/*
 * Saving a document either completes or leaves nothing behind.
 *
 * Order -> Purchase -> Save with "Deliver" and no address chosen was a 500:
 * PurchaseOrderService::create() called ->replicate() on Address::find(null),
 * after the PO header had already been written. Nothing was transactional, so
 * the half-made PO stayed. The address is now validated against the ones the
 * form offers, and every document save runs in one transaction.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderCreate;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderEdit;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteCreate;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrderLine;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;
use VentureDrake\LaravelCrm\Services\QuoteService;

class AtomicPurchaseOrderCreate extends PurchaseOrderCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class AtomicPurchaseOrderEdit extends PurchaseOrderEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

/**
 * A quote form whose custom-field save fails, after the service has written
 * the quote and its contact.
 */
class FailingCustomFieldsQuoteCreate extends QuoteCreate
{
    public function render()
    {
        return '<div></div>';
    }

    protected function saveCustomFields($model): void
    {
        throw new RuntimeException('custom fields failed');
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm purchase orders', 'create crm purchase orders', 'edit crm purchase orders',
        'view crm quotes', 'create crm quotes',
    ]);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    $team = Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    $this->teamAddress = $team->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'address' => 'Warehouse', 'line1' => '1 Dock Road',
    ]);

    foreach ([PurchaseOrder::class, Quote::class] as $model) {
        Pipeline::create(['external_id' => Str::uuid()->toString(), 'name' => class_basename($model), 'model' => $model])
            ->pipelineStages()->create(['external_id' => Str::uuid()->toString(), 'name' => 'Draft', 'order' => 0]);
    }

    $this->supplier = Organization::create(['name' => 'Supplier Co']);
});

test('a delivered purchase order needs an address', function () {
    Livewire::test(AtomicPurchaseOrderCreate::class)
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', null)
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'required_if']);

    expect(PurchaseOrder::count())->toBe(0);
});

test('a delivered purchase order only takes an address the form offers', function () {
    // Someone else's address - a person's, not the team's.
    $foreign = Person::create(['first_name' => 'Other'])->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'line1' => 'Elsewhere',
    ]);

    Livewire::test(AtomicPurchaseOrderCreate::class)
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', $foreign->id)
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'in']);

    expect(PurchaseOrder::count())->toBe(0);
});

test('a delivered purchase order saves with a team address', function () {
    Livewire::test(AtomicPurchaseOrderCreate::class)
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', $this->teamAddress->id)
        ->call('save')
        ->assertHasNoErrors();

    $purchaseOrder = PurchaseOrder::sole();

    expect($purchaseOrder->address->line1)->toBe('1 Dock Road')
        ->and($purchaseOrder->address->id)->not->toBe($this->teamAddress->id);
});

test('a pickup purchase order needs no address', function () {
    Livewire::test(AtomicPurchaseOrderCreate::class)
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->set('delivery_type', 'pickup')
        ->set('delivery_address', null)
        ->call('save')
        ->assertHasNoErrors();

    expect(PurchaseOrder::count())->toBe(1);
});

test('editing a purchase order accepts the address it was saved with', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $this->teamAddress->id,
    ]), null, $this->supplier);

    Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->assertSet('delivery_address', $purchaseOrder->address->id)
        ->call('save')
        ->assertHasNoErrors();
});

test('saving an edit leaves the delivery address it was saved with intact', function () {
    // update() used to rewrite the address from address_* fields the form
    // never sends, blanking it on every save.
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $this->teamAddress->id,
    ]), null, $this->supplier);
    $copy = $purchaseOrder->fresh()->address;

    Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->set('reference', 'PO-REF-2')
        ->call('save')
        ->assertHasNoErrors();

    $address = $purchaseOrder->fresh()->address;

    expect($address->id)->toBe($copy->id)
        ->and($address->line1)->toBe('1 Dock Road');
});

test('picking another address on edit gives the purchase order a copy of it', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $this->teamAddress->id,
    ]), null, $this->supplier);
    $oldCopy = $purchaseOrder->fresh()->address;

    $yard = Setting::where('name', 'team')->first()->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'address' => 'Yard', 'line1' => '9 Yard Lane',
    ]);

    Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->set('delivery_address', $yard->id)
        ->call('save')
        ->assertHasNoErrors();

    $address = $purchaseOrder->fresh()->address;

    expect($address->line1)->toBe('9 Yard Lane')
        ->and($address->id)->not->toBe($yard->id)
        ->and($oldCopy->fresh()->trashed())->toBeTrue()
        ->and($yard->fresh()->line1)->toBe('9 Yard Lane');
});

test('switching a pickup purchase order to delivery copies the address picked', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'pickup',
    ]), null, $this->supplier);

    Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', $this->teamAddress->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($purchaseOrder->fresh()->address->line1)->toBe('1 Dock Road');
});

test('the service copies only a team address, whoever calls it', function () {
    // The legacy controller posts straight to the service, unvalidated.
    $foreign = Person::create(['first_name' => 'Other'])->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'line1' => 'Elsewhere',
    ]);

    $created = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $foreign->id,
    ]));

    expect($created->fresh()->address)->toBeNull();

    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $this->teamAddress->id,
    ]));

    app(PurchaseOrderService::class)->update(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $foreign->id,
    ]), $purchaseOrder->fresh());

    expect($purchaseOrder->fresh()->address->line1)->toBe('1 Dock Road');
});

test('the edit form offers the address the purchase order has now', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $this->teamAddress->id,
    ]), null, $this->supplier);
    $copy = $purchaseOrder->fresh()->address;

    $options = Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->get('deliveryAddresses');

    expect($options[0])->toBe(['id' => $copy->id, 'name' => 'Current delivery address: Warehouse'])
        ->and(array_column($options, 'id'))->toContain($this->teamAddress->id);
});

test('a current address with no label is offered without stray separators', function () {
    $purchaseOrder = PurchaseOrder::create(['currency' => 'USD', 'delivery_type' => 'deliver']);
    $copy = $purchaseOrder->address()->create(['external_id' => Str::uuid()->toString(), 'line1' => '5 Quay St']);

    $options = Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->get('deliveryAddresses');

    expect($options[0])->toBe(['id' => $copy->id, 'name' => 'Current delivery address: 5 Quay St']);
});

test('a team address that is only a country is not offered or accepted', function () {
    // Issue #115: ", , United States" is not somewhere to deliver to.
    $countryOnly = Setting::where('name', 'team')->first()->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'country' => 'United States',
    ]);

    $component = Livewire::test(AtomicPurchaseOrderCreate::class);

    expect(array_column($component->get('deliveryAddresses'), 'id'))
        ->toContain($this->teamAddress->id)
        ->not->toContain($countryOnly->id);

    $component
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', $countryOnly->id)
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'in']);

    expect(PurchaseOrder::count())->toBe(0);
});

test('a team address with only a city can be delivered to', function () {
    $cityOnly = Setting::where('name', 'team')->first()->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'city' => 'Springfield', 'country' => 'United States',
    ]);

    Livewire::test(AtomicPurchaseOrderCreate::class)
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', $cityOnly->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(PurchaseOrder::sole()->address->city)->toBe('Springfield');
});

test('the service does not copy a team address that is only a country', function () {
    $countryOnly = Setting::where('name', 'team')->first()->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'country' => 'United States',
    ]);

    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $countryOnly->id,
    ]));

    expect($purchaseOrder->fresh()->address)->toBeNull();
});

test('a purchase order saved with a country-only address must be given a real one', function () {
    $purchaseOrder = PurchaseOrder::create([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'organization_id' => $this->supplier->id,
    ]);
    $copy = $purchaseOrder->address()->create(['external_id' => Str::uuid()->toString(), 'country' => 'United States']);

    $component = Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->assertSet('delivery_address', null);

    expect(array_column($component->get('deliveryAddresses'), 'id'))->not->toContain($copy->id);

    $component->call('save')->assertHasErrors(['delivery_address' => 'required_if']);

    $component->set('delivery_address', $copy->id)->call('save')->assertHasErrors(['delivery_address' => 'in']);

    $component->set('delivery_address', $this->teamAddress->id)->call('save')->assertHasNoErrors();

    expect($purchaseOrder->fresh()->address->line1)->toBe('1 Dock Road');
});

test('a pickup purchase order with a country-only address still saves', function () {
    $purchaseOrder = PurchaseOrder::create(['currency' => 'USD', 'delivery_type' => 'pickup']);
    $purchaseOrder->address()->create(['external_id' => Str::uuid()->toString(), 'country' => 'United States']);

    Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->call('save')
        ->assertHasNoErrors();
});

test('the offered delivery addresses cannot be changed from the browser', function () {
    Livewire::test(AtomicPurchaseOrderCreate::class)
        ->set('deliveryAddresses', [['id' => 999, 'name' => 'Injected']]);
})->throws(CannotUpdateLockedPropertyException::class);

test('editing a pickup purchase order keeps it a pickup and needs no address', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'pickup',
    ]), null, $this->supplier);

    Livewire::test(AtomicPurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->assertSet('delivery_type', 'pickup')
        ->set('reference', 'PO-REF-2')
        ->call('save')
        ->assertHasNoErrors();

    expect($purchaseOrder->fresh()->delivery_type)->toBe('pickup');
});

test('the service makes no address when it is given none', function () {
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => null,
    ]));

    expect($purchaseOrder->address)->toBeNull();
});

dataset('document services', [
    'quote' => [QuoteService::class, Quote::class, QuoteProduct::class],
    'order' => [OrderService::class, Order::class, OrderProduct::class],
    'invoice' => [InvoiceService::class, Invoice::class, InvoiceLine::class],
    'purchase order' => [PurchaseOrderService::class, PurchaseOrder::class, PurchaseOrderLine::class],
]);

test('a failure while saving the lines leaves no document', function (string $service, string $model, string $line) {
    $product = Product::create(['name' => 'Widget']);

    Event::listen("eloquent.creating: {$line}", function () {
        throw new RuntimeException('line failed');
    });

    expect(fn () => app($service)->create(new Request([
        'title' => 'Rolled back', 'currency' => 'USD', 'delivery_type' => 'pickup',
        'products' => [['id' => $product->id, 'quantity' => 1, 'unit_price' => 10, 'amount' => 10, 'comments' => null]],
    ])))->toThrow(RuntimeException::class, 'line failed');

    expect($model::withTrashed()->count())->toBe(0);
})->with('document services');

test('a failure saving custom fields leaves no quote and no new contact', function () {
    expect(fn () => Livewire::test(FailingCustomFieldsQuoteCreate::class)
        ->set('person_name', 'New Contact')
        ->set('title', 'Rolled back quote')
        ->call('save'))->toThrow(RuntimeException::class, 'custom fields failed');

    expect(Quote::withTrashed()->count())->toBe(0)
        ->and(Person::count())->toBe(0);
});
