<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Notifications\Concerns\ResolvesFeatureRecipients;
use VentureDrake\LaravelCrm\Notifications\FeatureStatusChangedNotification;
use VentureDrake\LaravelCrm\Notifications\FeatureSubmittedNotification;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;
use VentureDrake\LaravelCrm\Services\NumberGeneratorService;

class FeatureObserver
{
    use ResolvesFeatureRecipients;

    /**
     * Handle the feature "creating" event.
     *
     * @return void
     */
    public function creating(Feature $feature)
    {
        if (! $feature->external_id) {
            $feature->external_id = Uuid::uuid4()->toString();
        }

        if (! app()->runningInConsole()) {
            $feature->user_created_id = auth()->user()->id ?? null;
        }

        $feature->number = NumberGeneratorService::next(Feature::class, 1000);
        $feature->feature_id = 'F'.$feature->number;

        if (! $feature->feature_status_id) {
            $default = FeatureStatus::withoutGlobalScope(BelongsToTeamsScope::class)
                ->where('is_default', true)
                ->when($feature->team_id, fn ($q) => $q->where(function ($q) use ($feature) {
                    $q->whereNull('team_id')->orWhere('team_id', $feature->team_id);
                }))
                ->orderByRaw('team_id IS NULL')
                ->first();

            if ($default) {
                $feature->feature_status_id = $default->id;
            }
        }
    }

    /**
     * Handle the feature "created" event.
     *
     * @return void
     */
    public function created(Feature $feature)
    {
        $owners = $this->ownerRoleUsers($feature->team_id);

        if ($owners->isEmpty()) {
            return;
        }

        $notification = new FeatureSubmittedNotification($feature);

        $targets = $owners->map(fn ($user) => [
            'user' => $user,
            'role' => 'Owner',
            'notification' => $notification,
        ]);

        $this->dispatchNotifications($targets, $feature->user_created_id);
    }

    /**
     * Handle the feature "updating" event.
     *
     * @return void
     */
    public function updating(Feature $feature)
    {
        if (! app()->runningInConsole()) {
            $feature->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the feature "updated" event.
     *
     * @return void
     */
    public function updated(Feature $feature)
    {
        if (! $feature->wasChanged('feature_status_id')) {
            return;
        }

        $oldStatus = FeatureStatus::find($feature->getOriginal('feature_status_id'));
        $newStatus = $feature->status;

        $targets = collect();

        if ($submitter = $feature->submittedBy) {
            $targets->push([
                'user' => $submitter,
                'role' => 'submitter',
                'notification' => new FeatureStatusChangedNotification($feature, $oldStatus, $newStatus, 'submitter'),
            ]);
        }

        foreach ($feature->voters as $voter) {
            $targets->push([
                'user' => $voter,
                'role' => 'voter',
                'notification' => new FeatureStatusChangedNotification($feature, $oldStatus, $newStatus, 'voter'),
            ]);
        }

        if ($targets->isEmpty()) {
            return;
        }

        $this->dispatchNotifications($targets, auth()->id());
    }

    /**
     * Handle the feature "deleting" event.
     *
     * @return void
     */
    public function deleting(Feature $feature)
    {
        if (! app()->runningInConsole()) {
            $feature->user_deleted_id = auth()->user()->id ?? null;
            $feature->saveQuietly();
        }
    }

    /**
     * Handle the feature "restored" event.
     *
     * @return void
     */
    public function restored(Feature $feature)
    {
        if (! app()->runningInConsole()) {
            $feature->user_deleted_id = null;
            $feature->saveQuietly();
        }
    }
}
