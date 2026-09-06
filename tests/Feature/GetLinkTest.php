<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\GetLink;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Support\PortalLink;

/*
 * The "get link" button and the modal it opens.
 *
 * Like the PDF preview pair they are wired together by a string -- the
 * `crm-get-link` Livewire event -- rather than by anything the compiler
 * checks, so a rename on one side leaves both halves rendering happily and the
 * button silently dead. These tests pin the event name, the payload shape, and
 * the two things that actually carry risk: that the URL the button mints is a
 * signature the portal accepts, and that "mark as sent" cannot be driven past
 * the policy from a hand-edited request.
 */

/**
 * Invoice::getTitleAttribute() builds its value from the related organization
 * or person, so overriding it here is far cheaper than assembling that graph
 * just to get one apostrophe into the payload. PortalLink matches on
 * instanceof, so a subclass still resolves as an invoice.
 */
class GetLinkAwkwardTitleInvoice extends Invoice
{
    public function getTitleAttribute()
    {
        return "O'Brien & Sons \"Ltd\"";
    }
}

/**
 * Decode the payload the button hands to Livewire.
 *
 * Js::from hex-escapes every quote so nothing can terminate the surrounding
 * Alpine expression, which leaves the literal readable only to a JS string
 * parser. The outer json_decode plays that part -- " back to a quote --
 * and the inner one then parses the JSON that falls out.
 */
function getLinkPayload(string $html): array
{
    expect($html)->toContain('JSON.parse');

    preg_match("/window\.Livewire\.dispatch\('crm-get-link', JSON\.parse\('(.*?)'\)\)/", $html, $matches);

    expect($matches)->not->toBeEmpty('the dispatch payload did not render as a JSON.parse literal');

    return json_decode(json_decode('"'.$matches[1].'"'), true)['payload'];
}

it('renders the button as a stateless dispatch of the crm-get-link event', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1001',
        'currency' => 'USD',
        'total' => 110,
    ]);

    $html = Blade::render('<x-crm-get-link-button :model="$invoice" />', ['invoice' => $invoice]);

    // The event name the modal listens for.
    expect($html)->toContain("window.Livewire.dispatch('crm-get-link'");

    // Not $dispatch: a plain Alpine dispatch bubbles only through the button's
    // own Livewire root, and the modal is a sibling mounted in the layout.
    expect($html)->not->toContain("\$dispatch('crm-get-link'");

    // x-data is what gives the click handler an Alpine scope to evaluate in.
    expect($html)->toContain('x-data');

    $payload = getLinkPayload($html);

    // Everything the modal needs, resolved server-side at render time.
    expect($payload['type'])->toBe('invoice')
        ->and($payload['id'])->toBe($invoice->external_id)
        ->and($payload['url'])->toContain('/p/invoices/'.$invoice->external_id)
        ->and($payload['url'])->toContain('signature=');
});

it('escapes a title that would otherwise break out of the Alpine expression', function () {
    // The payload is interpolated into an x-on:click attribute. An apostrophe
    // in a customer name is the ordinary case that a naive '{{ $title }}'
    // would turn into a syntax error, killing the button for that row only.
    $invoice = GetLinkAwkwardTitleInvoice::create([
        'invoice_id' => 'INV1002',
        'currency' => 'USD',
        'total' => 110,
        // Observers are registered against Invoice::class and do not fire for a
        // subclass, so the external_id the portal route keys on is set here.
        'external_id' => (string) Str::uuid(),
    ]);

    $html = Blade::render('<x-crm-get-link-button :model="$invoice" />', ['invoice' => $invoice]);

    preg_match("/window\.Livewire\.dispatch\('crm-get-link', JSON\.parse\('(.*?)'\)\)/", $html, $matches);

    expect($matches)->not->toBeEmpty('the dispatch payload did not render as a JSON.parse literal');

    // Js::from hex-escapes every quote and ampersand, so none of the
    // characters that could terminate the expression early reach the attribute
    // raw...
    expect($matches[1])->not->toContain("'")
        ->and($matches[1])->not->toContain('"')
        ->and($matches[1])->not->toContain('&');

    // ...and the title still survives the round trip intact.
    expect(getLinkPayload($html)['title'])->toBe("O'Brien & Sons \"Ltd\"");
});

it('falls back to opening the portal page when no modal is mounted', function () {
    // layouts/app.blade.php is publishable, so a host that published it before
    // this feature shipped renders these buttons with nothing listening. The
    // modal sets window.crmGetLinkMounted on init; the button branches on it.
    $invoice = Invoice::create([
        'invoice_id' => 'INV1003',
        'currency' => 'USD',
    ]);

    $html = Blade::render('<x-crm-get-link-button :model="$invoice" />', ['invoice' => $invoice]);

    expect($html)->toContain('window.crmGetLinkMounted ? window.Livewire.dispatch(');

    preg_match("/window\.open\('([^']*)'/", $html, $matches);

    expect($matches)->not->toBeEmpty('no window.open fallback on the button');
    expect(str_replace('\\', '', $matches[1]))->toContain('/p/invoices/'.$invoice->external_id);

    expect($html)->toContain("'noopener'");
});

it('mints a signature the portal route accepts', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1004',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    // The whole point of the feature: paste this into a private window and the
    // portal page renders without a login.
    $this->get(PortalLink::for($invoice))->assertStatus(200);
});

it('mints a link that expires on the same schedule as the emailed one', function () {
    $invoice = Invoice::create([
        'invoice_id' => 'INV1005',
        'currency' => 'USD',
    ]);

    $url = PortalLink::for($invoice);

    // A "get link" copied out of the CRM and an emailed link are the same
    // link, so they must not outlive each other.
    expect(PortalLink::DAYS)->toBe(14);

    $this->travel(PortalLink::DAYS + 1)->days();

    $this->get($url)->assertStatus(401);
});

it('marks the record as sent when the tick is left on', function () {
    $this->actingAsUser(['crm_access' => 1]);

    $invoice = Invoice::create([
        'invoice_id' => 'INV1006',
        'currency' => 'USD',
        'sent' => 0,
    ]);

    Livewire::test(GetLink::class)
        ->dispatch('crm-get-link', payload: [
            'url' => PortalLink::for($invoice),
            'type' => 'invoice',
            'id' => $invoice->external_id,
            'title' => 'INV1006',
            'canMarkSent' => true,
        ])
        ->assertSet('show', true)
        // Pre-ticked, matching the accounting packages this mirrors.
        ->assertSet('markAsSent', true)
        ->call('confirm')
        ->assertSet('show', false)
        ->assertDispatched('crm-get-link-sent');

    expect((bool) $invoice->fresh()->sent)->toBeTrue();
});

it('leaves the record alone when the tick is cleared', function () {
    $this->actingAsUser(['crm_access' => 1]);

    $invoice = Invoice::create([
        'invoice_id' => 'INV1007',
        'currency' => 'USD',
        'sent' => 0,
    ]);

    Livewire::test(GetLink::class)
        ->dispatch('crm-get-link', payload: [
            'url' => PortalLink::for($invoice),
            'type' => 'invoice',
            'id' => $invoice->external_id,
            'canMarkSent' => true,
        ])
        ->set('markAsSent', false)
        ->call('confirm')
        ->assertNotDispatched('crm-get-link-sent');

    expect((bool) $invoice->fresh()->sent)->toBeFalse();
});

it('denies mark-as-sent to a user who cannot edit the record', function () {
    // Every property on the modal is browser-writable, so the tick is only as
    // safe as the policy call behind it.
    $this->actingAsUserWithPermissions(['view crm invoices']);

    $invoice = Invoice::create([
        'invoice_id' => 'INV1008',
        'currency' => 'USD',
        'sent' => 0,
    ]);

    Livewire::test(GetLink::class)
        ->dispatch('crm-get-link', payload: [
            'url' => PortalLink::for($invoice),
            'type' => 'invoice',
            'id' => $invoice->external_id,
            'canMarkSent' => true,
        ])
        ->call('confirm')
        ->assertForbidden();

    expect((bool) $invoice->fresh()->sent)->toBeFalse();
});

it('ignores a canMarkSent claim for a type that has no sent column', function () {
    $this->actingAsUser(['crm_access' => 1]);

    $quote = Quote::create([
        'quote_id' => 'Q1001',
        'title' => 'Website build',
        'currency' => 'USD',
    ]);

    // canMarkSent arrives from the browser. Quotes carry no `sent` column, so
    // taking it at face value would be an update() against a column that does
    // not exist.
    Livewire::test(GetLink::class)
        ->dispatch('crm-get-link', payload: [
            'url' => PortalLink::for($quote),
            'type' => 'quote',
            'id' => $quote->external_id,
            'canMarkSent' => true,
        ])
        ->set('markAsSent', true)
        ->call('confirm')
        ->assertSet('show', false)
        ->assertNotDispatched('crm-get-link-sent');
});

it('reports no mark-as-sent tick on a quote', function () {
    $quote = Quote::create([
        'quote_id' => 'Q1002',
        'title' => 'Website build',
        'currency' => 'USD',
    ]);

    expect(PortalLink::marksSent($quote))->toBeFalse();

    $html = Blade::render('<x-crm-get-link-button :model="$quote" />', ['quote' => $quote]);

    expect(getLinkPayload($html)['canMarkSent'])->toBeFalse();
});

it('hides the tick on a record that has already been sent', function () {
    // Signed in with edit rights so `sent` is the only thing under test.
    $this->actingAsUserWithPermissions(['view crm invoices', 'edit crm invoices']);

    $invoice = Invoice::create([
        'invoice_id' => 'INV1009',
        'currency' => 'USD',
        'sent' => 1,
    ]);

    $html = Blade::render('<x-crm-get-link-button :model="$invoice" />', ['invoice' => $invoice]);

    expect(getLinkPayload($html)['canMarkSent'])->toBeFalse();
});

it('offers the tick to a user who can edit the record', function () {
    $this->actingAsUserWithPermissions(['view crm invoices', 'edit crm invoices']);

    $invoice = Invoice::create([
        'invoice_id' => 'INV1010',
        'currency' => 'USD',
        'sent' => 0,
    ]);

    $html = Blade::render('<x-crm-get-link-button :model="$invoice" />', ['invoice' => $invoice]);

    expect(getLinkPayload($html)['canMarkSent'])->toBeTrue();
});

it('hides the tick from a user who can view but not edit the record', function () {
    // The button sits inside an @can('view crm invoices') block, but the tick
    // is a write and GetLink::confirm() authorizes it against `update` --
    // which needs `edit crm invoices`. Offering a pre-ticked box to a
    // view-only user would 403 their click on the modal's OK button.
    $this->actingAsUserWithPermissions(['view crm invoices']);

    $invoice = Invoice::create([
        'invoice_id' => 'INV1011',
        'currency' => 'USD',
        'sent' => 0,
    ]);

    $html = Blade::render('<x-crm-get-link-button :model="$invoice" />', ['invoice' => $invoice]);

    expect(getLinkPayload($html)['canMarkSent'])->toBeFalse();
});

it('resolves only the three portal types from a wire slug', function () {
    // modelFor() is the whitelist standing between a browser-supplied string
    // and a class name the modal instantiates.
    expect(PortalLink::modelFor('invoice'))->toBe(Invoice::class)
        ->and(PortalLink::modelFor('quote'))->toBe(Quote::class)
        ->and(PortalLink::modelFor('App\\Models\\User'))->toBeNull()
        ->and(PortalLink::modelFor('order'))->toBeNull();
});

it('mounts exactly one modal, in the layout rather than in any Livewire view', function () {
    // A per-row instance inside an index table would give every row its own
    // listener, so one click would open N stacked modals -- and each would
    // hold a different record's URL.
    $package = __DIR__.'/../../';

    $layout = file_get_contents($package.'resources/views/layouts/app.blade.php');

    expect(substr_count($layout, '<livewire:crm-get-link '))->toBe(1);

    $strays = [];

    $views = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($package.'resources/views/livewire', FilesystemIterator::SKIP_DOTS)
    );

    foreach ($views as $view) {
        if ($view->getExtension() !== 'php') {
            continue;
        }

        if (str_contains(file_get_contents($view->getPathname()), '<livewire:crm-get-link ')) {
            $strays[] = $view->getFilename();
        }
    }

    expect($strays)->toBe([]);
});

it('sits beside the preview button on every view that has one for a portal type', function () {
    // The two buttons are peers -- "see the document" and "share the
    // document" -- and a row that grew a preview button without the link is
    // the failure mode this catches. Orders and deliveries are excluded on
    // purpose: they have previews but no portal route to link to.
    $views = __DIR__.'/../../resources/views/livewire/';

    $missing = [];

    foreach (['invoices', 'quotes', 'purchase-orders'] as $directory) {
        foreach (glob($views.$directory.'/*.blade.php') as $view) {
            $contents = file_get_contents($view);

            if (str_contains($contents, '<x-crm-pdf-preview-button')
                && ! str_contains($contents, '<x-crm-get-link-button')) {
                $missing[] = basename($view);
            }
        }
    }

    expect($missing)->toBe([]);
});
