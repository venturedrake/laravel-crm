<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;

/**
 * The moves a quote makes through its life: sent, accepted, rejected and back.
 *
 * Each one sets the timestamp and the pipeline stage together, so the CRM, the
 * boards and the customer portal can't disagree about where a quote is. Stages
 * are found by name, the way the show, index and board pages always have.
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
     * A pipeline without the stage keeps the quote in its current one, because
     * clearing it would drop the quote off the board.
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
