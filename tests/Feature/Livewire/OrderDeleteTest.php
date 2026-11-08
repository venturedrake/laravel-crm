<?php

/*
 * #102: an order with invoices, deliveries or purchase orders cannot be
 * deleted.
 *
 * Every delete path only checked the `delete crm orders` permission, so an
 * invoiced (even part-paid) order could be deleted and its invoices left
 * pointing at nothing. The rule now lives on the order (canBeDeleted /
 * deleteBlockedReason) and in the delete policy, so the show page, index,
 * related index, web route and API are all held to it. A document whose
 * order was deleted before the fix shows the order number with a "deleted"
 * badge instead of a link that would 404.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryShow;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderShow;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderShow;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

class DeleteOrderShow extends OrderShow
{
    public function render()
    {
        return '<div></div>';
    }
}

class DeleteOrderIndex extends OrderIndex
{
    public function render()
    {
        return '<div></div>';
    }
}

class DeleteOrderRelatedIndex extends OrderRelatedIndex
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm orders', 'edit crm orders', 'delete crm orders',
        'view crm invoices', 'view crm deliveries', 'view crm purchase orders', 'view crm people',
    ]);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    $this->person = Person::create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
});

function orderDeleteTestOrder(): Order
{
    return Order::create(['currency' => 'USD', 'person_id' => test()->person->id])->fresh();
}

/**
 * An order with one child document of the given kind.
 */
function orderDeleteTestOrderWith(string $document): Order
{
    $order = orderDeleteTestOrder();

    match ($document) {
        'invoice' => Invoice::create(['order_id' => $order->id, 'currency' => 'USD', 'total' => 100, 'amount_paid' => 40, 'amount_due' => 60]),
        'delivery' => Delivery::create(['order_id' => $order->id]),
        'purchase order' => PurchaseOrder::create(['order_id' => $order->id, 'currency' => 'USD']),
    };

    return $order->fresh();
}

function orderDeleteTestReason(string $document): string
{
    return ucfirst(__('laravel-crm::lang.'.match ($document) {
        'invoice' => 'order_delete_invoiced',
        'delivery' => 'order_delete_delivered',
        'purchase order' => 'order_delete_purchase_ordered',
    }));
}

function orderDeleteTestToast($component): string
{
    // Mary shows a toast through a js effect.
    return json_encode($component->effects['xjs'] ?? [], JSON_UNESCAPED_UNICODE);
}

function orderDeleteTestComponentParams(string $component, Order $order): array
{
    return match ($component) {
        DeleteOrderShow::class, OrderShow::class => ['order' => $order],
        DeleteOrderRelatedIndex::class, OrderRelatedIndex::class => ['model' => test()->person],
        default => [],
    };
}

dataset('order documents', ['invoice', 'delivery', 'purchase order']);

dataset('order delete components', [
    'show' => DeleteOrderShow::class,
    'index' => DeleteOrderIndex::class,
    'related index' => DeleteOrderRelatedIndex::class,
]);

test('an order with a document cannot be deleted, and the policy says why', function (string $document) {
    $order = orderDeleteTestOrderWith($document);

    expect($order->canBeDeleted())->toBeFalse()
        ->and($order->deleteBlockedReason())->toBe(orderDeleteTestReason($document));

    $response = Gate::forUser(auth()->user())->inspect('delete', $order);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(orderDeleteTestReason($document));
})->with('order documents');

test('the reason names invoices first, then purchase orders, then deliveries', function () {
    $order = orderDeleteTestOrder();
    Delivery::create(['order_id' => $order->id]);

    expect($order->fresh()->deleteBlockedReason())->toBe(orderDeleteTestReason('delivery'));

    PurchaseOrder::create(['order_id' => $order->id, 'currency' => 'USD']);

    expect($order->fresh()->deleteBlockedReason())->toBe(orderDeleteTestReason('purchase order'));

    Invoice::create(['order_id' => $order->id, 'currency' => 'USD']);

    expect($order->fresh()->deleteBlockedReason())->toBe(orderDeleteTestReason('invoice'));
});

test('an order with no documents can be deleted', function () {
    $order = orderDeleteTestOrder();

    expect($order->canBeDeleted())->toBeTrue()
        ->and($order->deleteBlockedReason())->toBeNull()
        ->and(auth()->user()->can('delete', $order))->toBeTrue();
});

test('soft-deleted documents do not stop the order being deleted', function () {
    $order = orderDeleteTestOrder();
    Invoice::create(['order_id' => $order->id, 'currency' => 'USD'])->delete();
    Delivery::create(['order_id' => $order->id])->delete();
    PurchaseOrder::create(['order_id' => $order->id, 'currency' => 'USD'])->delete();

    expect($order->fresh()->canBeDeleted())->toBeTrue()
        ->and(auth()->user()->can('delete', $order->fresh()))->toBeTrue();
});

test('the delete permission is still needed for an order with no documents', function () {
    $this->actingAsUserWithPermissions(['view crm orders']);

    expect(auth()->user()->can('delete', orderDeleteTestOrder()))->toBeFalse();
});

test('the exists flags from withExists() answer without a query', function () {
    $order = orderDeleteTestOrderWith('purchase order');

    $loaded = Order::withExists(['invoices', 'deliveries', 'purchaseOrders'])->find($order->id);

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($loaded->deleteBlockedReason())->toBe(orderDeleteTestReason('purchase order'))
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

test('the show page, index and related index refuse to delete an order with a document', function (string $component, string $document) {
    $order = orderDeleteTestOrderWith($document);

    $test = Livewire::test($component, orderDeleteTestComponentParams($component, $order))
        ->call('delete', $order->id)
        ->assertOk();

    expect(Order::find($order->id))->not->toBeNull()
        ->and(orderDeleteTestToast($test))->toContain(orderDeleteTestReason($document));
})->with('order delete components')->with('order documents');

test('the show page, index and related index still delete an order with no documents', function (string $component) {
    $order = orderDeleteTestOrder();

    Livewire::test($component, orderDeleteTestComponentParams($component, $order))->call('delete', $order->id);

    expect(Order::find($order->id))->toBeNull()
        ->and(Order::withTrashed()->find($order->id)->trashed())->toBeTrue();
})->with('order delete components');

test('the web delete route refuses an invoiced order with a 403', function () {
    $order = orderDeleteTestOrderWith('invoice');

    $this->delete(route('laravel-crm.orders.destroy', $order))->assertForbidden();

    expect(Order::find($order->id))->not->toBeNull();
});

test('the API refuses to delete an order with a document with a 403 and the reason', function (string $document) {
    $order = orderDeleteTestOrderWith($document);

    $user = User::create([
        'name' => 'Order Delete API User',
        'email' => 'order-delete-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ]);

    $this->withHeaders([
        'Authorization' => 'Bearer '.$user->createToken('order-delete-test')->plainTextToken,
        'Accept' => 'application/json',
    ])
        ->deleteJson('/crm/api/v2/orders/'.$order->external_id)
        ->assertForbidden()
        ->assertJsonPath('message', orderDeleteTestReason($document));

    expect(Order::find($order->id))->not->toBeNull();
})->with('order documents');

test('the show page, index and related index offer Delete on a bare order but not on an invoiced one', function (string $component) {
    $bare = orderDeleteTestOrder();
    $invoiced = orderDeleteTestOrderWith('invoice');

    $html = fn (Order $order) => Livewire::test($component, orderDeleteTestComponentParams($component, $order))->html();

    $trigger = fn (Order $order) => 'modalDeleteOrder'.$order->id.'.showModal()';

    expect($html($bare))->toContain($trigger($bare));

    expect($html($invoiced))->not->toContain($trigger($invoiced))
        ->toContain(e(orderDeleteTestReason('invoice')));
})->with([
    'show' => OrderShow::class,
    'index' => OrderIndex::class,
    'related index' => OrderRelatedIndex::class,
]);

test('a document whose order was deleted still resolves it and shows its number without a link', function (string $document) {
    $order = orderDeleteTestOrder();

    $child = match ($document) {
        'invoice' => Invoice::create(['order_id' => $order->id, 'currency' => 'USD', 'person_id' => $this->person->id]),
        'delivery' => Delivery::create(['order_id' => $order->id]),
        'purchase order' => PurchaseOrder::create(['order_id' => $order->id, 'currency' => 'USD', 'person_id' => $this->person->id]),
    };

    // Deleted before this fix, when nothing stopped it.
    $order->deleteQuietly();

    expect($child->fresh()->order?->is($order))->toBeTrue();

    $orderUrl = route('laravel-crm.orders.show', $order);

    // The pages with an order column. The related indexes have none.
    $pages = match ($document) {
        'invoice' => [
            Livewire::test(InvoiceIndex::class),
        ],
        'delivery' => [
            Livewire::test(DeliveryShow::class, ['delivery' => $child->fresh()]),
            Livewire::test(DeliveryIndex::class),
        ],
        'purchase order' => [
            Livewire::test(PurchaseOrderShow::class, ['purchaseOrder' => $child->fresh()]),
            Livewire::test(PurchaseOrderIndex::class),
        ],
    };

    foreach ($pages as $page) {
        expect($page->html())
            ->toContain($order->order_id)
            ->toContain(__('laravel-crm::lang.deleted'))
            ->not->toContain($orderUrl);
    }
})->with('order documents');

test('a document whose order is live still links to it', function () {
    $order = orderDeleteTestOrder();
    $delivery = Delivery::create(['order_id' => $order->id]);

    expect(Livewire::test(DeliveryShow::class, ['delivery' => $delivery->fresh()])->html())
        ->toContain(route('laravel-crm.orders.show', $order));
});
