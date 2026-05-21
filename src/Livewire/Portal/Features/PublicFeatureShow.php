<?php

namespace VentureDrake\LaravelCrm\Livewire\Portal\Features;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Feature;

class PublicFeatureShow extends Component
{
    public Feature $feature;

    public function mount(Feature $feature): void
    {
        abort_unless($feature->is_public, 404);

        $this->feature = $feature;
    }

    public function hasVoted(): bool
    {
        if (! $userId = auth()->id()) {
            return false;
        }

        return $this->feature->voters()->where(
            config('laravel-crm.db_table_prefix').'feature_votes.user_id',
            $userId
        )->exists();
    }

    public function comments()
    {
        return $this->feature->comments()
            ->with('createdByUser')
            ->whereNull('parent_id')
            ->orderBy('created_at')
            ->get();
    }

    public function render()
    {
        return view('laravel-crm::livewire.portal.features.public-feature-show', [
            'comments' => $this->comments(),
            'hasVoted' => $this->hasVoted(),
        ]);
    }
}
