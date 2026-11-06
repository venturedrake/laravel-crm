<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use VentureDrake\LaravelCrm\Http\Helpers\CheckAmount;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

/**
 * The moves a quote makes through its life: sent, accepted, rejected and back.
 *
 * These keep the response and the pipeline stage in step, so the CRM, the boards
 * and the customer portal agree about where a quote is. A quote with an order can
 * still be moved between columns, and the quote form and the API can still set a
 * stage on its own. Stages are found by name, the way the show, index and board
 * pages always have, so a stage with another name doesn't count as a response.
 */
class QuoteStateService
{
    public const DRAFT = 'Draft';

    public const SENT = 'Sent';

    public const ACCEPTED = 'Accepted';

    public const REJECTED = 'Rejected';

    /**
     * Move a Draft quote to Sent. A quote that has moved on stays where it is,
     * however many times it is sent again.
     */
    public function markSent(Quote $quote): void
    {
        if ($quote->accepted_at || $quote->rejected_at || $quote->hasOrders()) {
            return;
        }

        // No stage at all shows in the board's first column, the same as Draft.
        if (! in_array($quote->pipelineStage?->name, [null, self::DRAFT], true)) {
            return;
        }

        $this->transition($quote, [], self::SENT);
    }

    public function accept(Quote $quote): void
    {
        $this->transition($quote, ['accepted_at' => Carbon::now()], self::ACCEPTED);
    }

    public function reject(Quote $quote): void
    {
        $this->transition($quote, ['rejected_at' => Carbon::now()], self::REJECTED);
    }

    /**
     * Returns false, changing nothing, once an order has been made from what
     * was accepted.
     */
    public function unaccept(Quote $quote): bool
    {
        if ($quote->hasOrders()) {
            return false;
        }

        $this->transition($quote, ['accepted_at' => null], self::DRAFT);

        return true;
    }

    public function unreject(Quote $quote): void
    {
        $this->transition($quote, ['rejected_at' => null], self::DRAFT);
    }

    /**
     * Why the board can't move a quote to a stage, or null when it can. A drag never
     * removes a response, because that is a decision with its own button and one a
     * customer may have made. Ordered, Won and a pipeline's own stages conflict with
     * nothing. Wrong amounts stop a response here as they do on the quote's menus. An
     * order was made from what was agreed, so its quote can go anywhere.
     */
    public function moveBlockedReason(Quote $quote, ?string $to): ?string
    {
        if ($quote->hasOrders()) {
            return null;
        }

        // A quote with both is never offered Unreject until it has been unaccepted.
        if ($quote->accepted_at && in_array($to, $quote->rejected_at
            ? [self::DRAFT, self::SENT, self::ACCEPTED, self::REJECTED]
            : [self::DRAFT, self::SENT, self::REJECTED], true)) {
            return ucfirst(__('laravel-crm::lang.quote_move_accepted'));
        }

        // Unreject is hidden while the amounts are wrong, so those come first.
        if ($quote->rejected_at && in_array($to, [self::DRAFT, self::SENT, self::ACCEPTED], true)) {
            return ucfirst(__('laravel-crm::lang.'.($this->amountsAreWrong($quote) ? 'quote_move_amounts' : 'quote_move_rejected')));
        }

        if (! $quote->accepted_at
            && ! $quote->rejected_at
            && in_array($to, [self::ACCEPTED, self::REJECTED], true)
            && $this->amountsAreWrong($quote)) {
            return ucfirst(__('laravel-crm::lang.quote_move_amounts'));
        }

        return null;
    }

    private function amountsAreWrong(Quote $quote): bool
    {
        return ! CheckAmount\subTotal($quote) || ! CheckAmount\total($quote);
    }

    /**
     * Record the response a board move into Accepted or Rejected stands for,
     * returning whether that changed the quote. A quote with an order only moves
     * column. See moveBlockedReason() for what never reaches here.
     */
    public function moved(Quote $quote, ?string $to): bool
    {
        if ($quote->hasOrders()) {
            return false;
        }

        $quote->fill(match ($to) {
            self::ACCEPTED => ['accepted_at' => $quote->accepted_at ?? Carbon::now()],
            self::REJECTED => ['rejected_at' => $quote->rejected_at ?? Carbon::now()],
            default => [],
        });

        return $quote->isDirty() && $quote->save();
    }

    /**
     * A pipeline without the stage keeps the quote in its current one, because
     * clearing it would send the quote to the board's first column.
     */
    private function transition(Quote $quote, array $attributes, string $stage): void
    {
        if ($stageId = $this->stageId($quote, $stage)) {
            $attributes['pipeline_stage_id'] = $stageId;
        }

        $quote->update($attributes);
        $quote->unsetRelation('pipelineStage');
    }

    /**
     * The quote's own pipeline first. The team scope stands down on the portal,
     * so a bare lookup there could land on another team's pipeline.
     */
    private function stageId(Quote $quote, string $name): ?int
    {
        $pipeline = $quote->pipeline ?? Pipeline::where('model', Quote::class)
            ->when(config('laravel-crm.teams'), fn ($query) => $query->where('team_id', $quote->team_id))
            ->first();

        return $pipeline?->pipelineStages()->where('name', $name)->first()?->id;
    }
}
