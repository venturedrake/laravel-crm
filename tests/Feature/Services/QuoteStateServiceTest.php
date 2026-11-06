<?php

/*
 * A quote's stage follows what happens to it, wherever that happens.
 *
 * Sending never moved the stage, and accepting in the customer portal set
 * accepted_at but left the stage at Draft, while the CRM pages each carried
 * their own copy of the change. QuoteStateService is now the one place, and
 * the show, index, board and send components and the portal all go through it.
 * The portal also refuses a quote that has been decided, ordered or has expired.
 */

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\Mailer\Exception\TransportException;
use VentureDrake\LaravelCrm\Http\Livewire\SendQuote as LegacySendQuote;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteSend;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteShow;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\QuoteStateService;

class StateQuoteShow extends QuoteShow
{
    public function render()
    {
        return '<div></div>';
    }
}

class StateQuoteIndex extends QuoteIndex
{
    public function render()
    {
        return '<div></div>';
    }
}

class StateQuoteBoard extends QuoteBoard
{
    public function render()
    {
        return '<div></div>';
    }
}

class StateQuoteSend extends QuoteSend
{
    public function render()
    {
        return '<div></div>';
    }
}

class StateLegacySendQuote extends LegacySendQuote
{
    public function render()
    {
        return '<div></div>';
    }

    // Livewire 2 API that NotifyToast calls once the email has gone. See PdfSendTest.
    public function dispatchBrowserEvent($event)
    {
        //
    }
}

beforeEach(function () {
    $this->pipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Quote Pipeline',
        'model' => Quote::class,
    ]);

    $this->stages = collect(['Draft', 'Sent', 'Accepted', 'Rejected', 'Ordered'])
        ->mapWithKeys(fn ($name, $order) => [$name => $this->pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => $name,
            'order' => $order,
        ])]);

    $this->service = app(QuoteStateService::class);
});

function quoteInStage(string $stage, array $attributes = []): Quote
{
    return Quote::create([
        'title' => 'Sample quote',
        'currency' => 'USD',
        'pipeline_id' => test()->pipeline->id,
        'pipeline_stage_id' => test()->stages[$stage]->id,
        ...$attributes,
    ]);
}

function portalProcess(Quote $quote, string $action)
{
    return test()->post(URL::temporarySignedRoute(
        'laravel-crm.portal.quotes.process',
        now()->addDays(7),
        ['quote' => $quote->external_id]
    ), ['action' => $action]);
}

function orderFrom(Quote $quote): Order
{
    return Order::create(['quote_id' => $quote->id, 'currency' => 'USD']);
}

/**
 * What the visitor was told, read from the flash queued for the next page.
 */
function portalFlash(): array
{
    return collect(session('flasher::envelopes', []))
        ->map(fn ($envelope) => (is_string($envelope) ? unserialize($envelope) : $envelope)->getMessage())
        ->all();
}

describe('the state service', function () {
    it('sets the timestamp and the stage together', function (string $action, string $column, string $stage) {
        $quote = quoteInStage('Sent');

        $this->service->{$action}($quote);

        $fresh = $quote->fresh();
        expect($fresh->{$column})->not->toBeNull()
            ->and($fresh->pipelineStage->name)->toBe($stage);
    })->with([
        'accept' => ['accept', 'accepted_at', 'Accepted'],
        'reject' => ['reject', 'rejected_at', 'Rejected'],
    ]);

    it('moves a Draft quote to Sent', function () {
        $quote = quoteInStage('Draft');

        $this->service->markSent($quote);

        expect($quote->fresh()->pipelineStage->name)->toBe('Sent')
            ->and($quote->pipelineStage->name)->toBe('Sent');
    });

    it('moves a quote with no stage to Sent', function () {
        $quote = quoteInStage('Draft', ['pipeline_stage_id' => null]);

        $this->service->markSent($quote);

        expect($quote->fresh()->pipelineStage->name)->toBe('Sent');
    });

    it('leaves a quote that has moved past Draft where it is when sent again', function (string $stage) {
        $quote = quoteInStage($stage);

        $this->service->markSent($quote);

        expect($quote->fresh()->pipelineStage->name)->toBe($stage);
    })->with(['Sent', 'Accepted', 'Rejected', 'Ordered']);

    it('does not move a quote back to Sent because its stage still says Draft', function (array $attributes) {
        $quote = quoteInStage('Draft', $attributes);

        $this->service->markSent($quote);

        expect($quote->fresh()->pipelineStage->name)->toBe('Draft');
    })->with([
        'accepted' => [fn () => ['accepted_at' => now()]],
        'rejected' => [fn () => ['rejected_at' => now()]],
    ]);

    it('does not move an ordered quote to Sent', function () {
        $quote = quoteInStage('Draft');
        orderFrom($quote);

        $this->service->markSent($quote);

        expect($quote->fresh()->pipelineStage->name)->toBe('Draft');
    });

    it('keeps the current stage when the pipeline has no matching stage', function () {
        $this->stages['Accepted']->delete();
        $quote = quoteInStage('Sent');

        $this->service->accept($quote);

        expect($quote->fresh()->accepted_at)->not->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Sent');
    });

    it('uses the quote\'s own pipeline rather than the first one for the model', function () {
        $other = Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'Another team\'s pipeline',
            'model' => Quote::class,
        ]);
        $otherStage = $other->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'Accepted',
            'order' => 0,
        ]);
        $quote = quoteInStage('Sent', [
            'pipeline_id' => $other->id,
            'pipeline_stage_id' => $otherStage->id,
        ]);

        $this->service->accept($quote);

        expect($quote->fresh()->pipeline_stage_id)->toBe($otherStage->id);
    });

    it('refuses to unaccept a quote with an order', function () {
        $quote = quoteInStage('Accepted', ['accepted_at' => now()]);
        orderFrom($quote);

        expect($this->service->unaccept($quote))->toBeFalse()
            ->and($quote->fresh()->accepted_at)->not->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Accepted');
    });
});

describe('sending from the CRM', function () {
    beforeEach(function () {
        $this->app->register(DomPdfServiceProvider::class);

        $this->actingAsUser(['crm_access' => 1]);
        Gate::before(fn () => true);

        app('laravel-crm.settings')->set('organization_name', 'Acme Pty Ltd');
        app('laravel-crm.settings')->forgetCache();

        Mail::fake();
    });

    function sendQuote(Quote $quote, string $component = StateQuoteSend::class)
    {
        return Livewire::test($component, ['quote' => $quote])
            ->set('to', 'customer@example.com')
            ->set('subject', 'Your quote')
            ->set('message', 'Please see attached')
            ->call('send')
            ->assertHasNoErrors();
    }

    it('moves a Draft quote to Sent and tells the page to refresh', function () {
        $quote = quoteInStage('Draft');

        sendQuote($quote)->assertDispatched('refreshQuote');

        expect($quote->fresh()->pipelineStage->name)->toBe('Sent');
    });

    it('moves a Draft quote to Sent through the legacy send component', function () {
        $quote = quoteInStage('Draft');

        sendQuote($quote, StateLegacySendQuote::class);

        expect($quote->fresh()->pipelineStage->name)->toBe('Sent');
    });

    it('leaves a Sent or Accepted quote in its stage', function (string $stage, array $attributes) {
        $quote = quoteInStage($stage, $attributes);

        sendQuote($quote);

        expect($quote->fresh()->pipelineStage->name)->toBe($stage);
    })->with([
        'sent' => ['Sent', []],
        'accepted' => ['Accepted', fn () => ['accepted_at' => now()]],
    ]);

    it('leaves the stage alone when the email could not be sent', function () {
        Mail::shouldReceive('send')->andThrow(new TransportException('Connection refused'));
        $quote = quoteInStage('Draft');

        Livewire::test(StateQuoteSend::class, ['quote' => $quote])
            ->set('to', 'customer@example.com')
            ->set('subject', 'Your quote')
            ->set('message', 'Please see attached')
            ->call('send');

        expect($quote->fresh()->pipelineStage->name)->toBe('Draft');
    });
});

describe('the customer portal', function () {
    it('sets the same stage and timestamp as the CRM', function (string $action, string $column, string $stage) {
        $quote = quoteInStage('Sent');

        portalProcess($quote, $action)->assertRedirect();

        $fresh = $quote->fresh();
        expect($fresh->{$column})->not->toBeNull()
            ->and($fresh->pipelineStage->name)->toBe($stage);
    })->with([
        'accept' => ['accept', 'accepted_at', 'Accepted'],
        'reject' => ['reject', 'rejected_at', 'Rejected'],
    ]);

    it('accepts a quote on the day it expires', function () {
        $quote = quoteInStage('Sent', ['expire_at' => now()->startOfDay()]);

        portalProcess($quote, 'accept');

        expect($quote->fresh()->accepted_at)->not->toBeNull();
    });

    it('counts down to the end of the day on a date-only expiry', function () {
        $this->travelTo('2026-11-06 10:00:00');
        $quote = quoteInStage('Sent', ['expire_at' => '2026-11-06 00:00:00']);

        $this->get(URL::temporarySignedRoute(
            'laravel-crm.portal.quotes.show',
            now()->addDays(7),
            ['quote' => $quote->external_id]
        ))->assertOk()->assertSee('Expires in 13 hours')->assertDontSee('hours ago');
    });

    it('gives a date-only expiry the whole day where midnight does not exist', function () {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Beirut');

        try {
            $quote = quoteInStage('Sent', ['expire_at' => '2027-03-28']);
            $this->travelTo($quote->fresh()->expire_at->copy()->setTime(10, 0));

            expect($quote->fresh()->expire_at->format('H:i'))->toBe('01:00')
                ->and($quote->fresh()->isExpired())->toBeFalse();
        } finally {
            date_default_timezone_set($timezone);
        }
    });

    it('treats an older blank expiry as no expiry', function (string $blank) {
        $quote = quoteInStage('Sent');
        Quote::whereKey($quote->id)->toBase()->update(['expire_at' => $blank]);

        expect($quote->fresh()->validUntil())->toBeNull()
            ->and($quote->fresh()->isExpired())->toBeFalse();

        $this->get(URL::temporarySignedRoute(
            'laravel-crm.portal.quotes.show',
            now()->addDays(7),
            ['quote' => $quote->external_id]
        ))->assertOk()->assertSee(ucfirst(__('laravel-crm::lang.pending')))->assertSee('value="accept"', false);

        portalProcess($quote, 'accept');

        expect($quote->fresh()->accepted_at)->not->toBeNull();
    })->with(['an empty string' => '', 'a zero date' => '0000-00-00 00:00:00']);

    it('keeps to a time of day set on the expiry', function () {
        $this->travelTo('2026-11-06 15:00:00');
        $quote = quoteInStage('Sent', ['expire_at' => '2026-11-06 09:00:00']);

        portalProcess($quote, 'accept')->assertRedirect();

        expect($quote->fresh()->accepted_at)->toBeNull()
            ->and(portalFlash())->toContain('This quote has expired');
    });

    it('accepts a quote before the time of day set on its expiry', function () {
        $this->travelTo('2026-11-06 08:00:00');
        $quote = quoteInStage('Sent', ['expire_at' => '2026-11-06 09:00:00']);

        portalProcess($quote, 'accept');

        expect($quote->fresh()->accepted_at)->not->toBeNull();
    });

    it('refuses to accept or reject a quote that has been decided', function (string $action, array $attributes, string $message) {
        $quote = quoteInStage('Sent', $attributes);
        $before = $quote->fresh()->only(['accepted_at', 'rejected_at', 'pipeline_stage_id']);

        portalProcess($quote, $action)->assertRedirect();

        expect($quote->fresh()->only(['accepted_at', 'rejected_at', 'pipeline_stage_id']))->toEqual($before)
            ->and(portalFlash())->toContain($message);
    })->with([
        'accept an accepted quote' => ['accept', fn () => ['accepted_at' => now()->subDay()], 'This quote has already been accepted'],
        'accept a rejected quote' => ['accept', fn () => ['rejected_at' => now()->subDay()], 'This quote has already been rejected'],
        'reject an accepted quote' => ['reject', fn () => ['accepted_at' => now()->subDay()], 'This quote has already been accepted'],
        'reject a rejected quote' => ['reject', fn () => ['rejected_at' => now()->subDay()], 'This quote has already been rejected'],
    ]);

    it('refuses to accept or reject a quote with an order', function (string $action) {
        $quote = quoteInStage('Sent');
        orderFrom($quote);

        portalProcess($quote, $action)->assertRedirect();

        expect($quote->fresh()->accepted_at)->toBeNull()
            ->and($quote->fresh()->rejected_at)->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Sent')
            ->and(portalFlash())->toContain('This quote has already been ordered');
    })->with(['accept', 'reject']);

    it('refuses to accept an expired quote', function () {
        $quote = quoteInStage('Sent', ['expire_at' => now()->subDay()]);

        portalProcess($quote, 'accept')->assertRedirect();

        expect($quote->fresh()->accepted_at)->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Sent')
            ->and(portalFlash())->toContain('This quote has expired');
    });

    it('still lets a customer reject an expired quote', function () {
        $quote = quoteInStage('Sent', ['expire_at' => now()->subDay()]);

        portalProcess($quote, 'reject');

        expect($quote->fresh()->rejected_at)->not->toBeNull();
    });

    it('hides accept and reject once the quote has an order', function () {
        $quote = quoteInStage('Sent');
        orderFrom($quote);

        $this->get(URL::temporarySignedRoute(
            'laravel-crm.portal.quotes.show',
            now()->addDays(7),
            ['quote' => $quote->external_id]
        ))->assertOk()->assertDontSee('value="accept"', false)->assertDontSee('value="reject"', false);
    });
});

describe('dragging on the board', function () {
    beforeEach(function () {
        $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);
    });

    function dragQuote(Quote $quote, string $from, string $to)
    {
        return Livewire::test(StateQuoteBoard::class)->call(
            'onStageChanged', $quote->id, test()->stages[$to]->id, [], [$quote->id], test()->stages[$from]->id,
        );
    }

    it('records a response when a quote enters its stage', function (string $to, string $set, string $clear) {
        $quote = quoteInStage('Sent');

        dragQuote($quote, 'Sent', $to)->assertOk();

        expect($quote->fresh()->{$set})->not->toBeNull()
            ->and($quote->fresh()->{$clear})->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe($to);
    })->with([
        'accepted' => ['Accepted', 'accepted_at', 'rejected_at'],
        'rejected' => ['Rejected', 'rejected_at', 'accepted_at'],
    ]);

    it('stops the customer answering a quote dragged to a response', function () {
        $quote = quoteInStage('Sent');

        dragQuote($quote, 'Sent', 'Accepted');

        $this->get(URL::temporarySignedRoute(
            'laravel-crm.portal.quotes.show',
            now()->addDays(7),
            ['quote' => $quote->external_id]
        ))->assertOk()->assertDontSee('value="accept"', false);
    });

    it('refuses a drag that would remove or swap a response', function (string $from, string $column, string $other, string $to) {
        $quote = quoteInStage($from, [$column => '2026-01-02 03:04:05']);

        $board = dragQuote($quote, $from, $to)->assertOk();

        expect($quote->fresh()->{$column}->format('Y-m-d H:i:s'))->toBe('2026-01-02 03:04:05')
            ->and($quote->fresh()->{$other})->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe($from)
            ->and($board->effects['html'] ?? null)->not->toBeNull();
    })->with([
        'accepted to draft' => ['Accepted', 'accepted_at', 'rejected_at', 'Draft'],
        'accepted to sent' => ['Accepted', 'accepted_at', 'rejected_at', 'Sent'],
        'accepted to rejected' => ['Accepted', 'accepted_at', 'rejected_at', 'Rejected'],
        'rejected to draft' => ['Rejected', 'rejected_at', 'accepted_at', 'Draft'],
        'rejected to sent' => ['Rejected', 'rejected_at', 'accepted_at', 'Sent'],
        'rejected to accepted' => ['Rejected', 'rejected_at', 'accepted_at', 'Accepted'],
    ]);

    it('leaves Unaccept as the way back', function () {
        $quote = quoteInStage('Accepted', ['accepted_at' => now()]);

        dragQuote($quote, 'Accepted', 'Draft');
        expect($quote->fresh()->accepted_at)->not->toBeNull();

        $this->service->unaccept($quote->fresh());

        expect($quote->fresh()->accepted_at)->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Draft');
    });

    it('keeps the date of a response the quote already has', function () {
        $quote = quoteInStage('Sent', ['accepted_at' => '2026-01-02 03:04:05']);

        dragQuote($quote, 'Sent', 'Accepted');

        expect($quote->fresh()->accepted_at->format('Y-m-d H:i:s'))->toBe('2026-01-02 03:04:05');
    });

    it('only changes the column of a quote with an order', function () {
        $quote = quoteInStage('Accepted', ['accepted_at' => '2026-01-02 03:04:05']);
        orderFrom($quote);

        dragQuote($quote, 'Accepted', 'Rejected')->assertOk();

        expect($quote->fresh()->pipelineStage->name)->toBe('Rejected')
            ->and($quote->fresh()->accepted_at)->not->toBeNull()
            ->and($quote->fresh()->rejected_at)->toBeNull();
    });

    it('keeps the response through the stages that follow it', function () {
        $this->stages['Won'] = $this->pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'Won',
            'order' => 9,
        ]);

        foreach (['Ordered', 'Won'] as $stage) {
            $quote = quoteInStage('Accepted', ['accepted_at' => now()]);

            dragQuote($quote, 'Accepted', $stage)->assertOk();

            expect($quote->fresh()->accepted_at)->not->toBeNull()
                ->and($quote->fresh()->isLocked())->toBeTrue()
                ->and($quote->fresh()->pipeline_stage_id)->toBe($this->stages[$stage]->id);
        }
    });

    it('follows a quote along the seeded pipeline, and not back', function () {
        $quote = quoteInStage('Draft');

        dragQuote($quote, 'Draft', 'Sent');
        expect($quote->fresh()->accepted_at)->toBeNull();

        dragQuote($quote, 'Sent', 'Accepted');
        expect($quote->fresh()->accepted_at)->not->toBeNull();

        dragQuote($quote, 'Accepted', 'Ordered');
        expect($quote->fresh()->pipelineStage->name)->toBe('Ordered')
            ->and($quote->fresh()->accepted_at)->not->toBeNull();

        dragQuote($quote, 'Ordered', 'Draft');
        expect($quote->fresh()->pipelineStage->name)->toBe('Ordered')
            ->and($quote->fresh()->accepted_at)->not->toBeNull();
    });

    it('gives the same answer whatever route the quote took', function () {
        $this->stages['Won'] = $this->pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'Won',
            'order' => 9,
        ]);
        $quote = quoteInStage('Accepted', ['accepted_at' => now()]);

        dragQuote($quote, 'Accepted', 'Won');
        dragQuote($quote, 'Won', 'Draft');

        expect($quote->fresh()->pipelineStage->name)->toBe('Won')
            ->and($quote->fresh()->accepted_at)->not->toBeNull();
    });

    it('protects a response the board has not shown yet', function () {
        $quote = quoteInStage('Sent');
        $this->service->accept($quote);

        dragQuote($quote, 'Sent', 'Draft')->assertOk();

        expect($quote->fresh()->accepted_at)->not->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Accepted');
    });

    it('protects a response on a quote already parked in its stage', function () {
        $quote = quoteInStage('Accepted');
        $board = Livewire::test(StateQuoteBoard::class);

        $this->service->accept($quote);

        $board->call('onStageChanged', $quote->id, $this->stages['Draft']->id, [], [$quote->id], $this->stages['Accepted']->id)
            ->assertOk();

        expect($quote->fresh()->accepted_at)->not->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Accepted');
    });

    it('moves a parked quote with no response, changing nothing else', function () {
        $quote = quoteInStage('Accepted');

        $board = dragQuote($quote, 'Accepted', 'Draft')->assertOk();

        expect($quote->fresh()->pipelineStage->name)->toBe('Draft')
            ->and($quote->fresh()->accepted_at)->toBeNull()
            ->and($board->effects['html'] ?? null)->toBeNull();
    });

    it('still moves a card from an old column with no response', function () {
        $quote = quoteInStage('Sent');

        dragQuote($quote, 'Draft', 'Rejected')->assertOk();

        expect($quote->fresh()->pipelineStage->name)->toBe('Rejected');
    });

    it('protects the response on a quote with no stage', function () {
        $quote = Quote::create([
            'title' => 'Stageless',
            'currency' => 'USD',
            'pipeline_id' => $this->pipeline->id,
            'rejected_at' => '2026-01-02 03:04:05',
        ]);

        dragQuote($quote, 'Draft', 'Sent')->assertOk();

        expect($quote->fresh()->rejected_at)->not->toBeNull()
            ->and($quote->fresh()->pipeline_stage_id)->toBeNull();
    });

    it('refuses a response when the amounts are wrong', function (string $to, string $column) {
        $quote = quoteInStage('Sent', ['subtotal' => 500, 'total' => 500]);

        $board = dragQuote($quote, 'Sent', $to)->assertOk();

        expect($quote->fresh()->{$column})->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Sent')
            ->and($board->effects['html'] ?? null)->not->toBeNull();
    })->with([
        'accepted' => ['Accepted', 'accepted_at'],
        'rejected' => ['Rejected', 'rejected_at'],
    ]);

    it('names a step the quote can really take', function (array $attributes, string $to, string $key) {
        $quote = quoteInStage('Sent', $attributes);

        expect($this->service->moveBlockedReason($quote, $to))->toBe(ucfirst(__('laravel-crm::lang.'.$key)));
    })->with([
        'rejected' => [['rejected_at' => '2026-01-02'], 'Draft', 'quote_move_rejected'],
        'rejected with wrong amounts' => [['rejected_at' => '2026-01-02', 'subtotal' => 500, 'total' => 500], 'Draft', 'quote_move_amounts'],
        'accepted with wrong amounts' => [['accepted_at' => '2026-01-02', 'subtotal' => 500, 'total' => 500], 'Draft', 'quote_move_accepted'],
        'both, heading for accepted' => [['accepted_at' => '2026-01-02', 'rejected_at' => '2026-01-02'], 'Accepted', 'quote_move_accepted'],
        'both, heading for rejected' => [['accepted_at' => '2026-01-02', 'rejected_at' => '2026-01-02'], 'Rejected', 'quote_move_accepted'],
    ]);

    it('moves a quote with wrong amounts between other stages', function () {
        $quote = quoteInStage('Sent', ['subtotal' => 500, 'total' => 500]);

        dragQuote($quote, 'Sent', 'Draft')->assertOk();

        expect($quote->fresh()->pipelineStage->name)->toBe('Draft');
    });

    it('redraws the board only when the response changes, for the card menu', function () {
        $board = dragQuote(quoteInStage('Sent'), 'Sent', 'Accepted');

        expect($board->effects['html'] ?? null)->not->toBeNull();

        $board = dragQuote(quoteInStage('Draft'), 'Draft', 'Sent');

        expect($board->effects['html'] ?? null)->toBeNull();
    });
});

describe('the CRM pages', function () {
    beforeEach(function () {
        $this->actingAsUserWithPermissions(['view crm quotes', 'edit crm quotes']);
    });

    dataset('pages', [
        'show' => fn () => [StateQuoteShow::class, fn (Quote $quote) => ['quote' => $quote]],
        'index' => fn () => [StateQuoteIndex::class, fn () => []],
        'board' => fn () => [StateQuoteBoard::class, fn () => []],
    ]);

    it('accepts, unaccepts, rejects and unrejects through the shared methods', function (array $page) {
        [$component, $params] = $page;
        $quote = quoteInStage('Sent');

        $test = Livewire::test($component, $params($quote));

        $test->call('accept', $quote->id);
        expect($quote->fresh()->accepted_at)->not->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Accepted');

        $test->call('unaccept', $quote->id);
        expect($quote->fresh()->accepted_at)->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Draft');

        $test->call('reject', $quote->id);
        expect($quote->fresh()->rejected_at)->not->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Rejected');

        $test->call('unreject', $quote->id);
        expect($quote->fresh()->rejected_at)->toBeNull()
            ->and($quote->fresh()->pipelineStage->name)->toBe('Draft');
    })->with('pages');
});
