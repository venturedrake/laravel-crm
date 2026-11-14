<?php

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderCreate;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\SettingEdit;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm purchase orders', 'create crm purchase orders', 'edit crm purchase orders',
        'edit crm settings',
    ]);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    $this->company = Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);

    Pipeline::create(['external_id' => Str::uuid()->toString(), 'name' => 'Purchases', 'model' => PurchaseOrder::class])
        ->pipelineStages()->create(['external_id' => Str::uuid()->toString(), 'name' => 'Draft', 'order' => 0]);

    $this->supplier = Organization::create(['name' => 'Supplier Co']);
});

test('new purchase orders without deliverable addresses default to pickup and save', function (bool $fromOrder, bool $countryOnly) {
    if ($countryOnly) {
        $this->company->addresses()->create([
            'external_id' => Str::uuid()->toString(), 'country' => 'Australia', 'line1' => ' ', 'city' => ' ',
        ]);
    }

    $order = $fromOrder ? Order::create(['currency' => 'USD']) : null;
    $parameters = $order ? ['fromModelType' => 'order', 'fromModelId' => $order->id] : [];

    Livewire::test(PurchaseOrderCreate::class, $parameters)
        ->assertSet('delivery_type', 'pickup')
        ->assertSet('deliveryAddresses', [])
        ->assertSee('No delivery addresses available.')
        ->assertSee('Settings → Addresses')
        ->assertSeeHtml('href="'.route('laravel-crm.settings.edit', ['tab' => 'general']).'#addresses"')
        ->assertDontSeeHtml('wire:model="delivery_address"')
        ->set('organization_id', $this->supplier->id)
        ->set('organization_name', $this->supplier->name)
        ->set('products', [])
        ->call('save')
        ->assertHasNoErrors();

    expect(PurchaseOrder::sole())
        ->delivery_type->toBe('pickup')
        ->address->toBeNull()
        ->order_id->toBe($order?->id);
})->with(['standalone' => false, 'from order' => true])->with(['no addresses' => false, 'country only' => true]);

test('a street or city keeps the deliver default', function (string $field) {
    $address = $this->company->addresses()->create([
        'external_id' => Str::uuid()->toString(), $field => 'Delivery destination',
    ]);

    $component = Livewire::test(PurchaseOrderCreate::class)
        ->assertSet('delivery_type', 'deliver')
        ->assertDontSee('No delivery addresses available.')
        ->assertSeeHtml('wire:model="delivery_address"');

    expect(array_column($component->get('deliveryAddresses'), 'id'))->toBe([$address->id]);

    $component->set('organization_id', $this->supplier->id)
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'required_if'])
        ->assertSee(ucfirst(__('laravel-crm::lang.delivery_address_required')));
})->with(['street' => 'line1', 'city' => 'city']);

test('selecting deliver with no eligible address shows an actionable error and saves nothing', function (?int $addressId) {
    Livewire::test(PurchaseOrderCreate::class)
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', $addressId)
        ->set('person_name', 'Unsaved Contact')
        ->call('save')
        ->assertHasErrors(['delivery_address' => $addressId === null ? 'required_if' : 'in'])
        ->assertSee(ucfirst(__('laravel-crm::lang.delivery_address_unavailable')))
        ->assertSeeHtml('role="alert"')
        ->assertDontSeeHtml('wire:model="delivery_address"');

    expect(PurchaseOrder::withTrashed()->count())->toBe(0)
        ->and(Person::count())->toBe(0);
})->with(['missing' => null, 'invalid' => 999]);

test('validation uses current server addresses when the offered address was removed', function () {
    $address = $this->company->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'line1' => '1 Dock Road',
    ]);
    $component = Livewire::test(PurchaseOrderCreate::class);
    $address->delete();

    $component->set('organization_id', $this->supplier->id)
        ->set('delivery_address', $address->id)
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'in'])
        ->assertSee(ucfirst(__('laravel-crm::lang.delivery_address_unavailable')));

    expect(PurchaseOrder::count())->toBe(0);
});

test('edit preserves the delivered purchase orders own copy after the company address is removed', function () {
    $address = $this->company->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'line1' => '1 Dock Road',
    ]);
    $purchaseOrder = app(PurchaseOrderService::class)->create(new Request([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'delivery_address' => $address->id,
    ]), null, $this->supplier);
    $copy = $purchaseOrder->fresh()->address;
    $address->delete();

    $component = Livewire::test(PurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->assertSet('delivery_type', 'deliver')
        ->assertSet('delivery_address', $copy->id)
        ->assertDontSee('No delivery addresses available.')
        ->assertSeeHtml('wire:model="delivery_address"');

    $component->set('delivery_address', null)
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'required_if'])
        ->assertSee(ucfirst(__('laravel-crm::lang.delivery_address_required')))
        ->assertDontSee(ucfirst(__('laravel-crm::lang.delivery_address_unavailable')))
        ->set('delivery_address', $copy->id)
        ->set('reference', 'Edited')
        ->call('save')
        ->assertHasNoErrors();

    expect($purchaseOrder->fresh())
        ->delivery_type->toBe('deliver')
        ->reference->toBe('Edited')
        ->and($purchaseOrder->fresh()->address)->id->toBe($copy->id)->line1->toBe('1 Dock Road');
});

test('an existing pickup stays pickup even when a company delivery address is available', function (bool $hasAddress) {
    if ($hasAddress) {
        $this->company->addresses()->create(['external_id' => Str::uuid()->toString(), 'city' => 'Sydney']);
    }
    $purchaseOrder = PurchaseOrder::create([
        'currency' => 'USD', 'delivery_type' => 'pickup', 'organization_id' => $this->supplier->id,
    ]);

    Livewire::test(PurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder])
        ->assertSet('delivery_type', 'pickup')
        ->call('save')
        ->assertHasNoErrors();

    expect($purchaseOrder->fresh()->delivery_type)->toBe('pickup');
})->with([false, true]);

test('an invalid historical delivery address is still rejected without silently changing to pickup', function () {
    $purchaseOrder = PurchaseOrder::create([
        'currency' => 'USD', 'delivery_type' => 'deliver', 'organization_id' => $this->supplier->id,
    ]);
    $copy = $purchaseOrder->address()->create(['external_id' => Str::uuid()->toString(), 'country' => 'Australia']);

    Livewire::test(PurchaseOrderEdit::class, ['purchaseOrder' => $purchaseOrder->fresh()])
        ->assertSet('delivery_type', 'deliver')
        ->assertSet('delivery_address', null)
        ->assertSet('deliveryAddresses', [])
        ->set('reference', 'Must not save')
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'required_if'])
        ->assertSee(ucfirst(__('laravel-crm::lang.delivery_address_unavailable')))
        ->set('delivery_address', $copy->id)
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'in']);

    expect($purchaseOrder->fresh())->delivery_type->toBe('deliver')->reference->toBeNull()
        ->and($purchaseOrder->fresh()->address->id)->toBe($copy->id);
});

test('users without settings permission see administrator guidance instead of the settings link', function () {
    $this->actingAsUserWithPermissions(['view crm purchase orders', 'create crm purchase orders']);

    Livewire::test(PurchaseOrderCreate::class)
        ->assertSet('delivery_type', 'pickup')
        ->assertSee('No delivery addresses available.')
        ->assertSee('Ask an administrator to add one, or use Pickup.')
        ->assertDontSeeHtml('href="'.route('laravel-crm.settings.edit', ['tab' => 'general']).'#addresses"');
});

test('the settings destination renders the address editor anchor on the general tab', function () {
    Livewire::withQueryParams(['tab' => 'general'])->test(SettingEdit::class)
        ->assertSet('tab', 'general')
        ->assertSeeHtml('id="addresses"');
});

test('another teams company address neither changes the default nor passes validation', function () {
    config()->set('laravel-crm.teams', true);
    auth()->user()->forceFill(['current_team_id' => 1])->save();
    auth()->user()->setRelation('currentTeam', new class
    {
        public int $id = 1;

        public function allUsers()
        {
            return collect();
        }
    });
    Setting::create(['name' => 'currency', 'value' => 'USD']);
    foreach ([1, 2] as $teamId) {
        Pipeline::create([
            'external_id' => Str::uuid()->toString(), 'name' => 'Purchases',
            'model' => PurchaseOrder::class, 'team_id' => $teamId,
        ])->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(), 'name' => 'Draft', 'order' => 0, 'team_id' => $teamId,
        ]);
    }
    $otherCompany = Setting::create(['name' => 'team', 'value' => 'related', 'team_id' => 2]);
    $foreign = $otherCompany->addresses()->create([
        'external_id' => Str::uuid()->toString(), 'line1' => 'Other team warehouse', 'team_id' => 2,
    ]);

    Livewire::test(PurchaseOrderCreate::class)
        ->assertSet('delivery_type', 'pickup')
        ->assertSet('deliveryAddresses', [])
        ->set('delivery_type', 'deliver')
        ->set('delivery_address', $foreign->id)
        ->set('person_name', 'Unsaved Contact')
        ->call('save')
        ->assertHasErrors(['delivery_address' => 'in'])
        ->assertSee(ucfirst(__('laravel-crm::lang.delivery_address_unavailable')));

    expect(PurchaseOrder::withoutGlobalScopes()->count())->toBe(0);

    auth()->user()->forceFill(['current_team_id' => 2])->save();
    auth()->user()->currentTeam->id = 2;
    Setting::create(['name' => 'currency', 'value' => 'USD']);
    $component = Livewire::test(PurchaseOrderCreate::class)->assertSet('delivery_type', 'deliver');
    expect(array_column($component->get('deliveryAddresses'), 'id'))->toBe([$foreign->id]);
});
