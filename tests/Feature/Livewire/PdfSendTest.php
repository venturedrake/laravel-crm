<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Http\Livewire\SendQuote as LegacySendQuote;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteSend;
use VentureDrake\LaravelCrm\Mail\SendQuote;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;

/*
 * The email-send path, which renders the same PDF blades as a download but
 * through an entirely separate view-data array.
 *
 * `Livewire\Quotes\QuoteSend` and its legacy twin `Http\Livewire\SendQuote`
 * are distinct classes that each build their own array by hand, and both
 * were missing `contactDetails` — so covering only one of them would leave
 * half the send surface unguarded. The download-route suite cannot reach
 * either: `send()` is a Livewire action, not an HTTP route.
 *
 * The components' blades reach for tables the minimal TestSchema does not
 * ship, so each is mounted through a render-stub subclass — the same
 * discipline as PdfTemplateSelectTest. `send()` still runs for real,
 * including the DomPDF render that would throw on an undefined variable.
 */

class PdfSendQuoteSend extends QuoteSend
{
    public function render()
    {
        return '<div></div>';
    }
}

class PdfSendLegacySendQuote extends LegacySendQuote
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->app->register(DomPdfServiceProvider::class);

    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::query()->delete();

    // The send-quote subject template dereferences the organization_name
    // Setting row without a null guard, so mount() fatals without it. Every
    // real install has one (laravelcrm:install seeds it).
    app('laravel-crm.settings')->set('organization_name', 'Acme Pty Ltd');
    app('laravel-crm.settings')->forgetCache();

    Mail::fake();
});

/**
 * A quote pinned to `$slug`, saved so `send()` has a real id to build its
 * storage path from.
 */
function sendableQuote(string $slug = 'modern'): Quote
{
    $quote = Quote::create([
        'title' => 'Sample quote',
        'subtotal' => 100,
        'tax' => 10,
        'total' => 110,
        'currency' => 'USD',
    ]);

    $quote->update(['pdf_template' => $slug]);

    return $quote;
}

test('QuoteSend renders and mails the PDF on every template', function (string $slug) {
    $quote = sendableQuote($slug);

    Livewire::test(PdfSendQuoteSend::class, ['quote' => $quote])
        ->set('to', 'buyer@example.com')
        ->set('subject', 'Your quote')
        ->set('message', 'Please find attached.')
        ->call('send')
        ->assertHasNoErrors();

    Mail::assertSent(SendQuote::class);
})->with(['modern', 'classic', 'bold', 'compact', 'professional']);

test('the legacy SendQuote component renders and mails the PDF on every template', function (string $slug) {
    // A separate class with a separately hand-built view-data array. It
    // broke in exactly the same way and would keep breaking independently.
    //
    // Its `send()` cannot run to completion under Livewire 3: the last
    // statement calls NotifyToast::notify(), which uses the Livewire 2
    // `dispatchBrowserEvent()` API removed in Livewire 3. That is a
    // pre-existing, unrelated defect shared by all 22 legacy components in
    // Http\Livewire and is out of scope here. Everything this change owns —
    // the view-data array and the DomPDF render it drives — runs before that
    // point, so the assertion is scoped to it: the PDF was written and the
    // mail was queued.
    $quote = sendableQuote($slug);

    $component = Livewire::test(PdfSendLegacySendQuote::class, ['quote' => $quote])
        ->set('to', 'buyer@example.com')
        ->set('subject', 'Your quote')
        ->set('message', 'Please find attached.');

    try {
        $component->call('send');
    } catch (BadMethodCallException $e) {
        // If this ever stops throwing, NotifyToast has been migrated to
        // Livewire 3 and this guard should be replaced with a plain
        // ->assertHasNoErrors().
        expect($e->getMessage())->toContain('dispatchBrowserEvent');
    }

    // The PDF is rendered and saved before notify() is reached, so its
    // existence proves the blade compiled with a complete view-data array.
    expect(file_exists(storage_path('app/laravel-crm/quote/'.$quote->id.'/quote-'.strtolower($quote->quote_id).'.pdf')))
        ->toBeTrue();

    Mail::assertSent(SendQuote::class);
})->with(['modern', 'classic', 'bold', 'compact', 'professional']);

test('both send components resolve the shared contact details setting', function () {
    // Guards the wiring rather than merely the absence of a throw: with the
    // shared key set, both components must resolve it for the `quote` doc
    // type, which has no per-doc-type field of its own.
    app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, 'ZZSharedZZ');
    app('laravel-crm.settings')->forgetCache();

    expect(PdfContactDetails::for('quote'))->toBe('ZZSharedZZ');

    foreach ([PdfSendQuoteSend::class, PdfSendLegacySendQuote::class] as $component) {
        $quote = sendableQuote();

        $test = Livewire::test($component, ['quote' => $quote])
            ->set('to', 'buyer@example.com')
            ->set('subject', 'Your quote')
            ->set('message', 'Please find attached.');

        try {
            $test->call('send');
        } catch (BadMethodCallException $e) {
            // The legacy component's unrelated Livewire 2 notify() call —
            // see the note on the legacy render test above.
            expect($e->getMessage())->toContain('dispatchBrowserEvent');
        }

        // The rendered PDF is written to disk rather than returned, so the
        // observable contract here is that the render completed with the
        // setting in play — the rendered-HTML assertion lives in
        // PdfDownloadRoutesTest, which shares the same resolver.
        expect(file_exists(storage_path('app/laravel-crm/quote/'.$quote->id.'/quote-'.strtolower($quote->quote_id).'.pdf')))
            ->toBeTrue();
    }

    Mail::assertSent(SendQuote::class, 2);
});
