<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryCreate;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryEdit;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Models\Delivery;

/**
 * Render-stub subclasses.
 *
 * Livewire renders a component on mount, and the CRM index/show/edit blades reach for
 * activity + contact tables the minimal TestSchema does not ship. Overriding only
 * render() leaves the real action methods -- and the $this->authorize() guards inside
 * them -- completely intact, so these tests still exercise the production
 * authorization path against the real policies.
 */
class AuthzDeliveryIndex extends DeliveryIndex
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzDeliveryCreate extends DeliveryCreate
{
    public function render()
    {
        return '<div></div>';
    }
}
class AuthzDeliveryEdit extends DeliveryEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

it('forbids deleting a delivery without the delete permission and leaves the record intact', function () {
    $this->actingAsUserWithPermissions(['view crm deliveries']);
    $record = Delivery::create(['delivery_expected' => now(), 'delivered_on' => now()]);

    Livewire::test(AuthzDeliveryIndex::class)
        ->call('delete', $record->id)
        ->assertForbidden();

    expect(Delivery::find($record->id))->not->toBeNull();
});

it('allows deleting a delivery with the delete permission', function () {
    $this->actingAsUserWithPermissions(['view crm deliveries', 'delete crm deliveries']);
    $record = Delivery::create(['delivery_expected' => now(), 'delivered_on' => now()]);

    Livewire::test(AuthzDeliveryIndex::class)
        ->call('delete', $record->id)
        ->assertOk();

    expect(Delivery::find($record->id))->toBeNull();
});

it('forbids creating a delivery without the create permission', function () {
    $this->actingAsUserWithPermissions(['view crm deliveries']);

    Livewire::test(AuthzDeliveryCreate::class)
        ->call('save')
        ->assertForbidden();
});

it('allows a user holding the create permission past the delivery create guard', function () {
    $this->actingAsUserWithPermissions(['view crm deliveries', 'create crm deliveries']);

    // Not forbidden: the guard passes and execution reaches validation, which is what
    // proves a seeded role that can create has not lost access.
    Livewire::test(AuthzDeliveryCreate::class)
        ->call('save')
        ->assertOk();
});

it('forbids saving a delivery edit without the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm deliveries']);
    $record = Delivery::create(['delivery_expected' => now(), 'delivered_on' => now()]);

    Livewire::test(AuthzDeliveryEdit::class, ['delivery' => $record])
        ->call('save')
        ->assertForbidden();
});

it('allows saving a delivery edit with the edit permission', function () {
    $this->actingAsUserWithPermissions(['view crm deliveries', 'edit crm deliveries']);
    $record = Delivery::create(['delivery_expected' => now(), 'delivered_on' => now()]);

    Livewire::test(AuthzDeliveryEdit::class, ['delivery' => $record])
        ->call('save')
        ->assertOk();
});
