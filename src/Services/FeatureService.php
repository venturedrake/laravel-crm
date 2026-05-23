<?php

namespace VentureDrake\LaravelCrm\Services;

use App\User;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureComment;
use VentureDrake\LaravelCrm\Models\FeatureVote;
use VentureDrake\LaravelCrm\Repositories\FeatureRepository;

class FeatureService
{
    /**
     * @var FeatureRepository
     */
    private $featureRepository;

    public function __construct(FeatureRepository $featureRepository)
    {
        $this->featureRepository = $featureRepository;
    }

    public function create(array $data, ?User $submittedBy = null): Feature
    {
        return Feature::create([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'is_public' => $data['is_public'] ?? false,
            'feature_status_id' => $data['feature_status_id'] ?? null,
            'submitted_by_user_id' => $submittedBy?->id ?? ($data['submitted_by_user_id'] ?? null),
            'user_owner_id' => $data['user_owner_id'] ?? null,
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
        ]);
    }

    public function update(Feature $feature, array $data): Feature
    {
        $feature->update(array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'is_public' => $data['is_public'] ?? null,
            'feature_status_id' => $data['feature_status_id'] ?? null,
            'user_owner_id' => $data['user_owner_id'] ?? null,
            'user_assigned_id' => $data['user_assigned_id'] ?? null,
        ], fn ($value) => $value !== null));

        return $feature;
    }

    public function vote(Feature $feature, User $user): FeatureVote
    {
        return FeatureVote::firstOrCreate([
            'feature_id' => $feature->id,
            'user_id' => $user->id,
        ]);
    }

    public function unvote(Feature $feature, User $user): bool
    {
        $vote = FeatureVote::where('feature_id', $feature->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $vote) {
            return false;
        }

        return (bool) $vote->delete();
    }

    public function comment(Feature $feature, User $user, string $body): FeatureComment
    {
        return $feature->comments()->create([
            'body' => $body,
            'user_created_id' => $user->id,
            'is_admin_reply' => $this->isAdminCommenter($user),
        ]);
    }

    private function isAdminCommenter(User $user): bool
    {
        if (! ($user->crm_access ?? false)) {
            return false;
        }

        if (! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return (bool) $user->hasPermissionTo('edit crm features');
        } catch (\Throwable) {
            return false;
        }
    }
}
