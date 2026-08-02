<?php

use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\DealProduct;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;

test('activities index requires view permission', function () {
    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode([])]);

    $this->get(route('laravel-crm.activities.index'))->assertForbidden();
});

test('activities index is accessible with view permission', function () {
    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm activities'])]);

    $this->get(route('laravel-crm.activities.index'))->assertOk();
});

test('activity show requires view permission', function () {
    $activity = Activity::create(['description' => 'Test activity']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode([])]);

    $this->get(route('laravel-crm.activities.show', $activity))->assertForbidden();
});

test('activity show is accessible with view permission', function () {
    $activity = Activity::create(['description' => 'Test activity']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm activities'])]);

    $this->get(route('laravel-crm.activities.show', $activity))->assertOk();
});

test('activity destroy requires delete permission', function () {
    $activity = Activity::create(['description' => 'Test activity']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm activities'])]);

    $this->delete(route('laravel-crm.activities.destroy', $activity))->assertForbidden();
});

test('activity destroy is accessible with delete permission', function () {
    $activity = Activity::create(['description' => 'Test activity']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['delete crm activities'])]);

    $this->delete(route('laravel-crm.activities.destroy', $activity))->assertSuccessful();
});

/*
 * DealProductController/QuoteProductController/OrderProductController render legacy v1 Blade
 * views (resources/v1/views/*) that aren't registered in this package's test harness. For their
 * create/edit actions we only assert the authorization gate itself (not forbidden), since full
 * view rendering is outside what this test environment supports.
 */

test('deal product create requires edit permission on the parent deal', function () {
    $deal = Deal::create(['title' => 'Big Deal']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm deals'])]);

    $this->get(route('laravel-crm.deal-products.create', $deal))->assertForbidden();
});

test('deal product create is accessible with edit permission on the parent deal', function () {
    $deal = Deal::create(['title' => 'Big Deal']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm deals'])]);

    $response = $this->get(route('laravel-crm.deal-products.create', $deal));

    expect($response->status())->not->toBe(403);
});

test('deal product edit requires edit permission on the parent deal', function () {
    $deal = Deal::create(['title' => 'Big Deal']);
    $product = DealProduct::create(['external_id' => 'dp-1', 'deal_id' => $deal->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm deals'])]);

    $this->get(route('laravel-crm.deal-products.edit', [$deal, $product]))->assertForbidden();
});

test('deal product edit is accessible with edit permission on the parent deal', function () {
    $deal = Deal::create(['title' => 'Big Deal']);
    $product = DealProduct::create(['external_id' => 'dp-1', 'deal_id' => $deal->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm deals'])]);

    $response = $this->get(route('laravel-crm.deal-products.edit', [$deal, $product]));

    expect($response->status())->not->toBe(403);
});

test('quote product create requires edit permission on the parent quote', function () {
    $quote = Quote::create(['title' => 'Big Quote']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm quotes'])]);

    $this->get(route('laravel-crm.quote-products.create', $quote))->assertForbidden();
});

test('quote product create is accessible with edit permission on the parent quote', function () {
    $quote = Quote::create(['title' => 'Big Quote']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm quotes'])]);

    $response = $this->get(route('laravel-crm.quote-products.create', $quote));

    expect($response->status())->not->toBe(403);
});

test('quote product edit requires edit permission on the parent quote', function () {
    $quote = Quote::create(['title' => 'Big Quote']);
    $product = QuoteProduct::create(['external_id' => 'qp-1', 'quote_id' => $quote->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm quotes'])]);

    $this->get(route('laravel-crm.quote-products.edit', [$quote, $product]))->assertForbidden();
});

test('quote product edit is accessible with edit permission on the parent quote', function () {
    $quote = Quote::create(['title' => 'Big Quote']);
    $product = QuoteProduct::create(['external_id' => 'qp-1', 'quote_id' => $quote->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm quotes'])]);

    $response = $this->get(route('laravel-crm.quote-products.edit', [$quote, $product]));

    expect($response->status())->not->toBe(403);
});

test('order product create requires edit permission on the parent order', function () {
    $order = Order::create(['description' => 'Big Order']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm orders'])]);

    $this->get(route('laravel-crm.order-products.create', $order))->assertForbidden();
});

test('order product create is accessible with edit permission on the parent order', function () {
    $order = Order::create(['description' => 'Big Order']);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm orders'])]);

    $response = $this->get(route('laravel-crm.order-products.create', $order));

    expect($response->status())->not->toBe(403);
});

test('order product edit requires edit permission on the parent order', function () {
    $order = Order::create(['description' => 'Big Order']);
    $product = OrderProduct::create(['external_id' => 'op-1', 'order_id' => $order->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm orders'])]);

    $this->get(route('laravel-crm.order-products.edit', [$order, $product]))->assertForbidden();
});

test('order product edit is accessible with edit permission on the parent order', function () {
    $order = Order::create(['description' => 'Big Order']);
    $product = OrderProduct::create(['external_id' => 'op-1', 'order_id' => $order->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm orders'])]);

    $response = $this->get(route('laravel-crm.order-products.edit', [$order, $product]));

    expect($response->status())->not->toBe(403);
});

test('deal create-product form requires create permission on deals', function () {
    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm deals'])]);

    $this->get(route('laravel-crm.deal-products.create-product'))->assertForbidden();
});

test('deal create-product form is accessible with create permission on deals', function () {
    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['create crm deals'])]);

    $response = $this->get(route('laravel-crm.deal-products.create-product'));

    expect($response->status())->not->toBe(403);
});

/*
 * index/store/show/update/destroy on the three product controllers are unimplemented and
 * abort(404). These assert 404 rather than 403 to prove the can: middleware actually resolved
 * the parent model — an unbound route parameter makes the gate deny everyone unconditionally,
 * so a 403 here would mean the middleware is inert and would silently break the day someone
 * implements one of these actions.
 */

test('deal product routes resolve the parent deal for the authorization check', function () {
    $deal = Deal::create(['title' => 'Big Deal']);
    $product = DealProduct::create(['external_id' => 'dp-1', 'deal_id' => $deal->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm deals', 'edit crm deals'])]);

    $this->get(route('laravel-crm.deal-products.index', $deal))->assertNotFound();
    $this->post(route('laravel-crm.deal-products.store', $deal))->assertNotFound();
    $this->get(route('laravel-crm.deal-products.show', [$deal, $product]))->assertNotFound();
    $this->put(route('laravel-crm.deal-products.update', [$deal, $product]))->assertNotFound();
    $this->delete(route('laravel-crm.deal-products.destroy', [$deal, $product]))->assertNotFound();
});

test('quote product routes resolve the parent quote for the authorization check', function () {
    $quote = Quote::create(['title' => 'Big Quote']);
    $product = QuoteProduct::create(['external_id' => 'qp-1', 'quote_id' => $quote->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm quotes', 'edit crm quotes'])]);

    $this->get(route('laravel-crm.quote-products.index', $quote))->assertNotFound();
    $this->post(route('laravel-crm.quote-products.store', $quote))->assertNotFound();
    $this->get(route('laravel-crm.quote-products.show', [$quote, $product]))->assertNotFound();
    $this->put(route('laravel-crm.quote-products.update', [$quote, $product]))->assertNotFound();
    $this->delete(route('laravel-crm.quote-products.destroy', [$quote, $product]))->assertNotFound();
});

test('order product routes resolve the parent order for the authorization check', function () {
    $order = Order::create(['description' => 'Big Order']);
    $product = OrderProduct::create(['external_id' => 'op-1', 'order_id' => $order->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['view crm orders', 'edit crm orders'])]);

    $this->get(route('laravel-crm.order-products.index', $order))->assertNotFound();
    $this->post(route('laravel-crm.order-products.store', $order))->assertNotFound();
    $this->get(route('laravel-crm.order-products.show', [$order, $product]))->assertNotFound();
    $this->put(route('laravel-crm.order-products.update', [$order, $product]))->assertNotFound();
    $this->delete(route('laravel-crm.order-products.destroy', [$order, $product]))->assertNotFound();
});

/*
 * The product routes are scope bound, so a child can only be addressed through the parent it
 * actually belongs to. Without that, "can you edit this deal" would be checked against a deal
 * that has nothing to do with the product line being addressed.
 */

test('deal product edit rejects a product belonging to another deal', function () {
    $dealA = Deal::create(['title' => 'Deal A']);
    $dealB = Deal::create(['title' => 'Deal B']);
    $productOfB = DealProduct::create(['external_id' => 'dp-b', 'deal_id' => $dealB->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm deals'])]);

    $this->get(route('laravel-crm.deal-products.edit', [$dealA, $productOfB]))->assertNotFound();
});

test('quote product edit rejects a product belonging to another quote', function () {
    $quoteA = Quote::create(['title' => 'Quote A']);
    $quoteB = Quote::create(['title' => 'Quote B']);
    $productOfB = QuoteProduct::create(['external_id' => 'qp-b', 'quote_id' => $quoteB->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm quotes'])]);

    $this->get(route('laravel-crm.quote-products.edit', [$quoteA, $productOfB]))->assertNotFound();
});

test('order product edit rejects a product belonging to another order', function () {
    $orderA = Order::create(['description' => 'Order A']);
    $orderB = Order::create(['description' => 'Order B']);
    $productOfB = OrderProduct::create(['external_id' => 'op-b', 'order_id' => $orderB->id]);

    $this->actingAsUser(['crm_access' => 1, 'crm_permissions' => json_encode(['edit crm orders'])]);

    $this->get(route('laravel-crm.order-products.edit', [$orderA, $productOfB]))->assertNotFound();
});
