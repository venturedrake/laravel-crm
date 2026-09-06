<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class HostTeamController extends Controller
{
    public function create()
    {
        return view('laravel-crm::host-teams.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $teamClass = $this->resolveHostTeamModel($user);

        if (! $teamClass) {
            abort(404);
        }

        if (method_exists($user, 'ownedTeams')) {
            $team = $user->ownedTeams()->create($this->buildAttributes($teamClass, $data['name']));
        } else {
            $attributes = $this->buildAttributes($teamClass, $data['name']);
            $ownerColumn = $this->ownerColumn($teamClass);
            if ($ownerColumn) {
                $attributes[$ownerColumn] = $user->id;
            }
            $team = $teamClass::create($attributes);
        }

        if (method_exists($user, 'switchTeam')) {
            $user->switchTeam($team);
        } elseif (Schema::hasColumn($user->getTable(), 'current_team_id')) {
            $user->forceFill(['current_team_id' => $team->id])->save();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // The relation still holds the team we just switched away from, and
        // anything reading it later this request — the settings cache key
        // included — would resolve to the wrong tenant.
        $user->unsetRelation('currentTeam');
        app('laravel-crm.settings')->forgetCache();

        return redirect()->route('laravel-crm.dashboard');
    }

    protected function resolveHostTeamModel($user): ?string
    {
        $configured = config('laravel-crm.host_team_model');
        if ($configured && class_exists($configured)) {
            return $configured;
        }

        if (method_exists($user, 'ownedTeams')) {
            $related = $user->ownedTeams()->getRelated();

            return get_class($related);
        }

        if (class_exists(Team::class)) {
            return Team::class;
        }

        return null;
    }

    protected function buildAttributes(string $teamClass, string $name): array
    {
        $attributes = ['name' => $name];
        $model = new $teamClass;
        $table = $model->getTable();

        if (Schema::hasColumn($table, 'personal_team')) {
            $attributes['personal_team'] = false;
        }

        return $attributes;
    }

    protected function ownerColumn(string $teamClass): ?string
    {
        $model = new $teamClass;
        $table = $model->getTable();

        foreach (['user_id', 'owner_id', 'created_by'] as $candidate) {
            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
