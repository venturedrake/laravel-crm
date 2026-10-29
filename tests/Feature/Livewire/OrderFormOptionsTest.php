<?php

/*
 * config('laravel-crm.orders.form') trims the order form. Every key defaults to
 * the full form, and a hidden field is skipped by validation and save rather
 * than cleared, so an order edited through a trimmed form keeps its stored
 * description, labels, custom field values and addresses of a hidden type.
 *
 * The real components and blades are mounted, so "not rendered" is asserted
 * against the markup the browser would get.
 */

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderCreate;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderEdit;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\FormOptions;

beforeEach(function () {
    $this->user = $this->actingAsUserWithPermissions([
        'view crm orders', 'create crm orders', 'edit crm orders',
    ]);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);

    $pipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Order Pipeline',
        'model' => Order::class,
    ]);
    $pipeline->pipelineStages()->create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Draft',
        'order' => 0,
    ]);

    $this->billingType = AddressType::firstOrCreate(['name' => 'Billing']);
    $this->shippingType = AddressType::firstOrCreate(['name' => 'Shipping']);

    $this->field = Field::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Pallet count',
        'key' => 'pallet_count',
        'type' => 'text',
        'required' => true,
    ]);
    FieldModel::create([
        'external_id' => Str::uuid()->toString(),
        'field_id' => $this->field->id,
        'model' => Order::class,
    ]);

    $this->labelA = Label::create(['external_id' => Str::uuid()->toString(), 'name' => 'Urgent', 'hex' => 'ff0000']);
    $this->labelB = Label::create(['external_id' => Str::uuid()->toString(), 'name' => 'Wholesale', 'hex' => '00ff00']);
});

/**
 * An order holding a value in every field the form can hide.
 */
function seededOrder(?int $ownerId = null): Order
{
    $organization = Organization::create(['name' => 'Urban Nurseries']);

    $order = Order::create([
        'organization_id' => $organization->id,
        'description' => 'Leave at the side gate',
        'currency' => 'USD',
        'user_owner_id' => $ownerId,
    ]);

    $order->labels()->sync([test()->labelA->id]);
    // HasCrmFields already created the row with the field's default.
    $order->fields()->updateOrCreate(['field_id' => test()->field->id], ['value' => '12']);

    $order->addresses()->create([
        'external_id' => Str::uuid()->toString(),
        'address_type_id' => test()->billingType->id,
        'line1' => '1 Billing St',
        'city' => 'Sydney',
        'country' => 'Australia',
        'primary' => 1,
    ]);
    $order->addresses()->create([
        'external_id' => Str::uuid()->toString(),
        'address_type_id' => test()->shippingType->id,
        'line1' => '2 Shipping Rd',
        'city' => 'Sydney',
        'country' => 'Australia',
        'primary' => 1,
    ]);

    return $order->fresh();
}

function storedFieldValue(Order $order): ?string
{
    return $order->fresh()->fields()->where('field_id', test()->field->id)->value('value');
}

/**
 * The order form's own markup. The nested crm-model-products component
 * renders a product modal with its own description field, so assertions stop
 * where that component starts.
 */
function orderFormHtml($component): string
{
    return Str::before($component->html(), 'wire:name="crm-model-products"');
}

/**
 * Order::getBillingAddress() looks addresses up by fixed ids, so look them up
 * by the seeded type instead.
 */
function orderAddress(Order $order, AddressType $type)
{
    return $order->addresses()->where('address_type_id', $type->id)->first();
}

// Defaults ---------------------------------------------------------------

test('the default order form renders every optional field', function () {
    $order = seededOrder($this->user->id);

    $html = orderFormHtml(Livewire::test(OrderEdit::class, ['order' => $order]));

    expect($html)->toContain('wire:model="description"')
        ->toContain("entangle('labels')")
        ->toContain('Pallet count')
        ->toContain('Unallocated')
        ->toContain('wire:model="addresses.billing.line1"')
        ->toContain('wire:model="addresses.shipping.line1"')
        ->toContain("entangle('selectedAddressTab')");
});

test('the default order form saves every field as before', function () {
    $order = seededOrder($this->user->id);

    Livewire::test(OrderEdit::class, ['order' => $order])
        ->set('description', 'Ring on arrival')
        ->set('labels', [$this->labelB->id])
        ->set('fields.'.$this->field->id, '20')
        ->set('addresses.billing.line1', '9 New Billing St')
        ->set('user_owner_id', 0)
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->description)->toBe('Ring on arrival')
        ->and($order->labels->pluck('id')->all())->toBe([$this->labelB->id])
        ->and(storedFieldValue($order))->toBe('20')
        ->and(orderAddress($order, $this->billingType)->line1)->toBe('9 New Billing St')
        ->and(orderAddress($order, $this->shippingType)->line1)->toBe('2 Shipping Rd')
        ->and((int) $order->user_owner_id)->toBe(0);
});

test('the default order form still demands a required custom field', function () {
    $order = seededOrder($this->user->id);

    Livewire::test(OrderEdit::class, ['order' => $order])
        ->set('fields.'.$this->field->id, '')
        ->call('save')
        ->assertHasErrors('fields.'.$this->field->id);
});

test('the default create form saves both addresses', function () {
    Livewire::test(OrderCreate::class)
        ->set('organization_name', 'Urban Nurseries')
        ->set('fields.'.$this->field->id, '3')
        ->set('addresses.billing.line1', '1 Billing St')
        ->set('addresses.shipping.line1', '2 Shipping Rd')
        ->call('save')
        ->assertHasNoErrors();

    $order = Order::latest('id')->first();

    expect($order->addresses()->count())->toBe(2)
        ->and(orderAddress($order, $this->billingType)->line1)->toBe('1 Billing St')
        ->and(storedFieldValue($order))->toBe('3');
});

// Hidden fields ------------------------------------------------------------

test('a hidden description is not rendered and survives an edit', function () {
    config(['laravel-crm.orders.form.description' => false]);
    $order = seededOrder($this->user->id);

    $component = Livewire::test(OrderEdit::class, ['order' => $order]);

    expect(orderFormHtml($component))->not->toContain('wire:model="description"');

    // Even a value pushed at the hidden property is not written.
    $component->set('description', 'Tampered')
        ->call('save')
        ->assertHasNoErrors();

    expect($order->fresh()->description)->toBe('Leave at the side gate');
});

test('hidden labels are not rendered and stay attached after an edit', function () {
    config(['laravel-crm.orders.form.labels' => false]);
    $order = seededOrder($this->user->id);

    $component = Livewire::test(OrderEdit::class, ['order' => $order]);

    expect(orderFormHtml($component))->not->toContain("entangle('labels')");

    $component->set('labels', [])
        ->call('save')
        ->assertHasNoErrors();

    expect($order->fresh()->labels->pluck('id')->all())->toBe([$this->labelA->id]);
});

test('hidden custom fields are not rendered, not demanded and keep their value', function () {
    config(['laravel-crm.orders.form.custom_fields' => false]);
    $order = seededOrder($this->user->id);

    $component = Livewire::test(OrderEdit::class, ['order' => $order]);

    expect(orderFormHtml($component))->not->toContain('Pallet count');

    // Required, but hidden: validation skips it rather than failing.
    $component->set('fields.'.$this->field->id, '')
        ->call('save')
        ->assertHasNoErrors();

    expect(storedFieldValue($order))->toBe('12');
});

test('a hidden required custom field does not block creating an order', function () {
    config(['laravel-crm.orders.form.custom_fields' => false]);

    Livewire::test(OrderCreate::class)
        ->set('organization_name', 'Urban Nurseries')
        ->call('save')
        ->assertHasNoErrors();

    expect(Order::count())->toBe(1)
        ->and(storedFieldValue(Order::first()))->toBeNull();
});

test('an order created from a quote keeps the quote description when the field is hidden', function () {
    config([
        'laravel-crm.orders.form.description' => false,
        'laravel-crm.orders.form.custom_fields' => false,
    ]);

    $organization = Organization::create(['name' => 'Urban Nurseries']);
    $quote = Quote::create([
        'organization_id' => $organization->id,
        'title' => 'Spring planting',
        'description' => 'Quoted for 40 hedges',
        'currency' => 'USD',
    ]);

    $component = Livewire::test(OrderCreate::class, ['fromModelType' => 'quote', 'fromModelId' => $quote->id]);

    expect(orderFormHtml($component))->not->toContain('wire:model="description"');

    $component->set('description', 'Tampered')
        ->call('save')
        ->assertHasNoErrors();

    expect(Order::first()->description)->toBe('Quoted for 40 hedges');
});

// Owner ---------------------------------------------------------------------

test('without unallocated_owner the owner select has no option 0', function () {
    config(['laravel-crm.orders.form.unallocated_owner' => false]);
    $order = seededOrder($this->user->id);

    $html = orderFormHtml(Livewire::test(OrderEdit::class, ['order' => $order]));

    expect($html)->not->toContain('Unallocated')
        ->not->toContain('value="0"')
        ->toContain('value="'.$this->user->id.'"');
});

test('without unallocated_owner an order with no owner is preset to the current user', function () {
    config(['laravel-crm.orders.form.unallocated_owner' => false]);
    $order = seededOrder(null);

    Livewire::test(OrderEdit::class, ['order' => $order])
        ->assertSet('user_owner_id', $this->user->id);
});

test('with unallocated_owner an order with no owner stays unallocated', function () {
    $order = seededOrder(null);

    Livewire::test(OrderEdit::class, ['order' => $order])
        ->assertSet('user_owner_id', null);
});

test('without unallocated_owner saving with no owner fails validation', function () {
    config(['laravel-crm.orders.form.unallocated_owner' => false]);
    $order = seededOrder($this->user->id);

    Livewire::test(OrderEdit::class, ['order' => $order])
        ->set('user_owner_id', null)
        ->call('save')
        ->assertHasErrors(['user_owner_id' => 'required']);

    Livewire::test(OrderEdit::class, ['order' => $order])
        ->set('user_owner_id', 0)
        ->call('save')
        ->assertHasErrors('user_owner_id');

    expect((int) $order->fresh()->user_owner_id)->toBe($this->user->id);
});

test('usersOptions keeps Unallocated by default and drops it on request', function () {
    $with = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false);
    $without = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false, false);

    expect($with[0])->toBe(['id' => 0, 'name' => 'Unallocated'])
        ->and(collect($without)->pluck('id')->all())->not->toContain(0)
        ->and(count($with))->toBe(count($without) + 1);
});

// Addresses ----------------------------------------------------------------

test('with only shipping listed the billing tab and the tab switcher are gone', function () {
    config(['laravel-crm.orders.form.addresses' => ['shipping']]);
    $order = seededOrder($this->user->id);

    $component = Livewire::test(OrderEdit::class, ['order' => $order])
        ->assertSet('selectedAddressTab', 'shipping');

    expect(orderFormHtml($component))->toContain('wire:model="addresses.shipping.line1"')
        ->toContain('Shipping address')
        ->not->toContain('addresses.billing.')
        ->not->toContain("entangle('selectedAddressTab')");
});

test('with only shipping listed a billing address survives an edit untouched', function () {
    config(['laravel-crm.orders.form.addresses' => ['shipping']]);
    $order = seededOrder($this->user->id);
    $billing = orderAddress($order, $this->billingType);

    Livewire::test(OrderEdit::class, ['order' => $order])
        // Whatever reaches the hidden billing row never reaches the database.
        ->set('addresses.billing.country', null)
        ->set('addresses.billing.line1', 'Tampered')
        ->set('addresses.shipping.line1', '5 New Shipping Rd')
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh();
    $stillThere = $order->addresses()->find($billing->id);

    expect($stillThere)->not->toBeNull()
        ->and($stillThere->line1)->toBe('1 Billing St')
        ->and($stillThere->country)->toBe('Australia')
        ->and($stillThere->updated_at->equalTo($billing->updated_at))->toBeTrue()
        ->and(orderAddress($order, $this->shippingType)->line1)->toBe('5 New Shipping Rd')
        ->and($order->addresses()->count())->toBe(2);
});

test('with only shipping listed a new order gets only a shipping address', function () {
    config([
        'laravel-crm.orders.form.addresses' => ['shipping'],
        'laravel-crm.orders.form.custom_fields' => false,
    ]);

    Livewire::test(OrderCreate::class)
        ->set('organization_name', 'Urban Nurseries')
        ->set('addresses.shipping.line1', '2 Shipping Rd')
        ->call('save')
        ->assertHasNoErrors();

    $order = Order::first();

    expect($order->addresses()->count())->toBe(1)
        ->and(orderAddress($order, $this->shippingType)->line1)->toBe('2 Shipping Rd')
        ->and(orderAddress($order, $this->billingType))->toBeNull();
});

test('address type names are matched case-insensitively and unknown names ignored', function () {
    config(['laravel-crm.orders.form.addresses' => ['Shipping', 'postal', 'BILLING']]);

    expect(FormOptions::for('orders')->addressTypes())
        ->toBe(['shipping', 'billing']);
});

test('an empty address list hides the addresses card and leaves every address alone', function () {
    config(['laravel-crm.orders.form.addresses' => []]);
    $order = seededOrder($this->user->id);

    $component = Livewire::test(OrderEdit::class, ['order' => $order]);

    expect(orderFormHtml($component))->not->toContain('addresses.billing.')
        ->not->toContain('addresses.shipping.');

    $component->call('save')->assertHasNoErrors();

    expect($order->fresh()->addresses()->count())->toBe(2);
});

test('a host config that predates the orders key gets the full form', function () {
    config(['laravel-crm.orders' => null]);

    $form = FormOptions::for('orders');

    expect($form->shows('description'))->toBeTrue()
        ->and($form->shows('labels'))->toBeTrue()
        ->and($form->shows('custom_fields'))->toBeTrue()
        ->and($form->allowsUnallocatedOwner())->toBeTrue()
        ->and($form->addressTypes())->toBe(['billing', 'shipping']);
});
