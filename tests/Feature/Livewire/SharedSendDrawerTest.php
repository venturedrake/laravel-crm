<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceSend;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteSend;
use VentureDrake\LaravelCrm\Mail\SendQuote;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * Every row of a list used to mount its own Send component, each rendering the email
 * templates in mount() whether or not the row was ever sent, and the quote board did
 * the same per card. One instance now serves a whole page: the row dispatches an event
 * and the template only renders when the drawer opens.
 *
 * Sharing an instance moves the record from something the page chose to something the
 * browser names, so open() has to treat the id the way any other action treats input.
 */

class SharedQuoteSend extends QuoteSend
{
    public function render()
    {
        return '<div></div>';
    }
}

class SharedInvoiceSend extends InvoiceSend
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->app->register(DomPdfServiceProvider::class);

    Setting::query()->delete();
    app('laravel-crm.settings')->set('organization_name', 'Acme Pty Ltd');
    app('laravel-crm.settings')->forgetCache();
});

dataset('send drawers', [
    'quote' => [
        SharedQuoteSend::class, 'quote', 'quote-send', 'showSendQuote', 'crm quotes',
        fn (array $attributes = []) => Quote::create(['title' => 'Q', 'currency' => 'USD', 'total' => 110] + $attributes),
    ],
    'invoice' => [
        SharedInvoiceSend::class, 'invoice', 'invoice-send', 'showSendInvoice', 'crm invoices',
        fn (array $attributes = []) => Invoice::create(['currency' => 'USD', 'total' => 110] + $attributes),
    ],
]);

function sendDrawerPerson(): Person
{
    $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $person->emails()->create(['address' => 'jane@example.com', 'primary' => 1]);

    return $person;
}

it('renders nothing until the drawer opens', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    $this->actingAsUserWithPermissions(["view {$permission}"]);

    Livewire::test($component, [$parameter => $record(['person_id' => sendDrawerPerson()->id])])
        ->assertSet('to', null)
        ->assertSet('subject', null)
        ->assertSet('message', null)
        ->assertSet($drawer, false);
})->with('send drawers');

it('fills the drawer from its own record on open', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    $this->actingAsUserWithPermissions(["view {$permission}"]);

    $test = Livewire::test($component, [$parameter => $record(['person_id' => sendDrawerPerson()->id])])
        ->call('open')
        ->assertSet('to', 'jane@example.com')
        ->assertSet($drawer, true);

    expect($test->get('subject'))->toContain('Acme Pty Ltd')
        ->and($test->get('message'))->not->toBeEmpty();
})->with('send drawers');

it('opens a shared instance for the record a row names', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    $this->actingAsUserWithPermissions(["view {$permission}"]);

    $first = $record(['person_id' => sendDrawerPerson()->id]);
    $second = $record();

    $test = Livewire::test($component)
        ->dispatch($event, id: $first->id)
        ->assertSet($drawer, true)
        ->assertSet('to', 'jane@example.com');

    expect($test->get($parameter)->id)->toBe($first->id);

    $test->set('cc', true)
        ->dispatch($event, id: $second->id)
        ->assertSet('to', null)
        ->assertSet('cc', null);

    expect($test->get($parameter)->id)->toBe($second->id);
})->with('send drawers');

it('leaves a page-bound instance alone when the event names another record', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    $this->actingAsUserWithPermissions(["view {$permission}"]);

    $bound = $record();
    $other = $record();

    $test = Livewire::test($component, [$parameter => $bound])
        ->dispatch($event, id: $other->id)
        ->assertSet($drawer, false);

    expect($test->get($parameter)->id)->toBe($bound->id);

    $test->dispatch($event, id: $bound->id)->assertSet($drawer, true);
})->with('send drawers');

it('refuses to open a record the user cannot view', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    $this->actingAsUserWithPermissions([]);

    Livewire::test($component)
        ->dispatch($event, id: $record()->id)
        ->assertForbidden();
})->with('send drawers');

it('does not open a record from another team', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    config()->set('laravel-crm.teams', true);

    $this->actingAsUserWithPermissions(["view {$permission}"], ['current_team_id' => 1]);

    $test = Livewire::test($component)
        ->dispatch($event, id: $record(['team_id' => 2])->id)
        ->assertSet($drawer, false)
        ->assertSet('subject', null);

    expect($test->get($parameter))->toBeNull();

    config()->set('laravel-crm.teams', false);
})->with('send drawers');

it('ignores an event for a record that does not exist', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    $this->actingAsUserWithPermissions(["view {$permission}"]);

    Livewire::test($component)
        ->dispatch($event, id: 999999)
        ->assertSet($drawer, false);
})->with('send drawers');

it('will not send from a shared instance nothing has been opened on', function (string $component, string $parameter, string $event, string $drawer, string $permission, Closure $record) {
    Mail::fake();
    $this->actingAsUserWithPermissions(["view {$permission}", "edit {$permission}"]);

    Livewire::test($component)
        ->set(['to' => 'buyer@example.com', 'subject' => 'S', 'message' => 'M'])
        ->call('send')
        ->assertNotFound();

    Mail::assertNothingSent();
})->with('send drawers');

it('fills the drawer again when it reopens after a send', function () {
    Mail::fake();
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    $quote = Quote::create(['title' => 'Q', 'currency' => 'USD', 'total' => 110, 'person_id' => sendDrawerPerson()->id]);

    Livewire::test(SharedQuoteSend::class, ['quote' => $quote])
        ->call('open')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('showSendQuote', false)
        ->assertSet('to', null)
        ->call('open')
        ->assertSet('to', 'jane@example.com');

    Mail::assertSent(SendQuote::class);
});

it('mounts one Send drawer on the quote list, however many rows it has', function () {
    $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);

    $quotes = collect(range(1, 3))->map(fn () => Quote::create(['title' => 'Q', 'currency' => 'USD']));

    $test = Livewire::test(QuoteIndex::class);

    expect(substr_count($test->html(), '&quot;name&quot;:&quot;crm-quote-send&quot;'))->toBe(1);

    $quotes->each(fn (Quote $quote) => $test->assertSeeHtml("\$dispatch('quote-send', { id: {$quote->id} })"));
});

it('mounts one Send drawer on the invoice list, however many rows it has', function () {
    $this->actingAsUserWithPermissions(['view crm invoices']);

    $invoices = collect(range(1, 3))->map(fn () => Invoice::create(['currency' => 'USD']));

    $test = Livewire::test(InvoiceIndex::class);

    expect(substr_count($test->html(), '&quot;name&quot;:&quot;crm-invoice-send&quot;'))->toBe(1);

    $invoices->each(fn (Invoice $invoice) => $test->assertSeeHtml("\$dispatch('invoice-send', { id: {$invoice->id} })"));
});
