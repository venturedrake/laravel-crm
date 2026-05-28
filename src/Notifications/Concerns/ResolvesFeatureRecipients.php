<?php

namespace VentureDrake\LaravelCrm\Notifications\Concerns;

use App\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait ResolvesFeatureRecipients
{
    protected function ownerRoleUsers(?int $teamId): Collection
    {
        $userModel = config('auth.providers.users.model', User::class);

        $roleTable = config('permission.table_names.roles', 'roles');
        $modelHasRoles = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');

        try {
            if (! Schema::hasTable($modelHasRoles) || ! Schema::hasTable($roleTable)) {
                return collect();
            }
        } catch (Throwable $e) {
            return collect();
        }

        $query = DB::table($modelHasRoles)
            ->join($roleTable, $modelHasRoles.'.role_id', '=', $roleTable.'.id')
            ->where($roleTable.'.name', 'Owner');

        if (config('laravel-crm.teams') && $teamId) {
            $query->where($modelHasRoles.'.team_id', $teamId);
        }

        $userIds = $query->pluck($modelHasRoles.'.'.$morphKey);

        if ($userIds->isEmpty()) {
            return collect();
        }

        $users = $userModel::whereIn('id', $userIds)->get();

        return $users->reject(fn ($user) => empty($user->email))->values();
    }

    protected function dispatchNotifications(Collection $targets, ?int $excludeUserId): void
    {
        $seen = [];

        foreach ($targets as $target) {
            $user = $target['user'] ?? null;
            $notification = $target['notification'] ?? null;

            if (! is_object($user) || ! $notification instanceof Notification) {
                continue;
            }

            $userId = $user->id ?? null;

            if ($userId === null || isset($seen[$userId])) {
                continue;
            }

            if ($excludeUserId !== null && $userId === $excludeUserId) {
                continue;
            }

            if (empty($user->email)) {
                continue;
            }

            $seen[$userId] = true;

            $user->notify($notification);
        }
    }
}
