<?php

use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

/**
 * The public portal is anonymous by design — a signed link, no login — so
 * BelongsToTeamsScope never engages there. That left every settings read on the
 * page unscoped: `pluck()` keys by name, so whichever team's row the database
 * listed last supplied the logo, the From block and the ABN for every tenant's
 * documents. The controllers now pin the settings service to the document's own
 * team before rendering anything.
 */
beforeEach(function () {
    config()->set('laravel-crm.teams', true);
});

afterEach(function () {
    config()->set('laravel-crm.teams', false);
});

function portalTeamSetting(int $teamId, string $name, string $value): void
{
    Setting::withoutGlobalScopes()->create([
        'name' => $name,
        'value' => $value,
        'team_id' => $teamId,
    ]);
}

/**
 * Two tenants with the same setting name and different values — the only shape
 * in which the leak is visible.
 *
 * Team 1's row is written last on purpose. An unscoped read plucks by name, so
 * the last row wins: every document below belongs to team 2, and writing team 1
 * second is what makes the leak show up as the wrong name rather than the right
 * one by accident.
 */
function portalTwoBrandedTeams(): void
{
    portalTeamSetting(2, 'organization_name', 'Beta Team Co');
    portalTeamSetting(1, 'organization_name', 'Alpha Team Co');
}

function portalInvoiceFor(?int $teamId, string $invoiceId): Invoice
{
    return Invoice::create([
        'invoice_id' => $invoiceId,
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
        'team_id' => $teamId,
    ]);
}

function portalSignedUrl(string $route, string $parameter, string $externalId): string
{
    return URL::temporarySignedRoute($route, now()->addDays(7), [$parameter => $externalId]);
}

test('an anonymous portal invoice shows its own teams from name', function () {
    portalTwoBrandedTeams();

    $invoice = portalInvoiceFor(2, 'INV2001');

    $response = $this->get(portalSignedUrl(
        'laravel-crm.portal.invoices.show',
        'invoice',
        $invoice->external_id
    ));

    $response->assertStatus(200);
    $response->assertSee('Beta Team Co', false);
    $response->assertDontSee('Alpha Team Co', false);
});

test('warming the page with one teams invoice does not poison anothers', function () {
    // The regression the cache-key partition exists to prevent: the first
    // request through fills the map, and every later one is served from it.
    portalTwoBrandedTeams();

    $teamOne = portalInvoiceFor(1, 'INV2002');
    $teamTwo = portalInvoiceFor(2, 'INV2003');

    $this->get(portalSignedUrl('laravel-crm.portal.invoices.show', 'invoice', $teamOne->external_id))
        ->assertStatus(200)
        ->assertSee('Alpha Team Co', false);

    $response = $this->get(portalSignedUrl(
        'laravel-crm.portal.invoices.show',
        'invoice',
        $teamTwo->external_id
    ));

    $response->assertStatus(200);
    $response->assertSee('Beta Team Co', false);
    $response->assertDontSee('Alpha Team Co', false);
});

test('an invoice with no team borrows neither teams from name', function () {
    // A document that predates teams. A blank From block is the correct
    // outcome; whichever tenant sorted last is the bug.
    portalTwoBrandedTeams();

    $invoice = portalInvoiceFor(null, 'INV2004');

    $response = $this->get(portalSignedUrl(
        'laravel-crm.portal.invoices.show',
        'invoice',
        $invoice->external_id
    ));

    $response->assertStatus(200);
    $response->assertDontSee('Alpha Team Co', false);
    $response->assertDontSee('Beta Team Co', false);
});

test('a leftover portal team id config does not override the documents own team', function () {
    // `LARAVEL_CRM_PORTAL_TEAM_ID` used to be read ahead of the document's own
    // team_id, which re-opened this very leak on any install that had set it:
    // a team 2 invoice printed team 1's organisation name, ABN and logo. The
    // key is no longer read at all, so a stale .env value changes nothing.
    config()->set('laravel-crm.portal.team_id', 1);

    portalTwoBrandedTeams();

    $invoice = portalInvoiceFor(2, 'INV2006');

    $response = $this->get(portalSignedUrl(
        'laravel-crm.portal.invoices.show',
        'invoice',
        $invoice->external_id
    ));

    $response->assertStatus(200);
    $response->assertSee('Beta Team Co', false);
    $response->assertDontSee('Alpha Team Co', false);
});

test('a staff member of one team can open another teams signed link', function () {
    // The signature is what authorises these routes. Who happens to be signed
    // in should not decide whether the link opens — the team scope used to
    // filter the route-model binding and hand them a 404 instead.
    portalTwoBrandedTeams();

    $invoice = portalInvoiceFor(2, 'INV2005');

    $this->actingAs(User::create([
        'name' => 'Team One Staff',
        'email' => 'portal-team-one@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
        'current_team_id' => 1,
        'team_ids' => json_encode([1]),
    ]));

    $response = $this->get(portalSignedUrl(
        'laravel-crm.portal.invoices.show',
        'invoice',
        $invoice->external_id
    ));

    $response->assertStatus(200);
    $response->assertSee('Beta Team Co', false);
    $response->assertDontSee('Alpha Team Co', false);
});

test('an anonymous portal quote shows its own teams from name', function () {
    portalTwoBrandedTeams();

    $quote = Quote::create([
        'title' => 'Sample quote',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
        'team_id' => 2,
    ]);

    $response = $this->get(portalSignedUrl(
        'laravel-crm.portal.quotes.show',
        'quote',
        $quote->external_id
    ));

    $response->assertStatus(200);
    $response->assertSee('Beta Team Co', false);
    $response->assertDontSee('Alpha Team Co', false);
});

test('an anonymous portal purchase order shows its own teams from name', function () {
    portalTwoBrandedTeams();

    $purchaseOrder = PurchaseOrder::create([
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
        'team_id' => 2,
    ]);

    $response = $this->get(portalSignedUrl(
        'laravel-crm.portal.purchase-orders.show',
        'purchaseOrder',
        $purchaseOrder->external_id
    ));

    $response->assertStatus(200);
    $response->assertSee('Beta Team Co', false);
    $response->assertDontSee('Alpha Team Co', false);
});
