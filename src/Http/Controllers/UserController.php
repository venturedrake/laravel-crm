<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use DB;
use Illuminate\Support\Facades\Hash;
use VentureDrake\LaravelCrm\Http\Requests\InviteUserRequest;
use VentureDrake\LaravelCrm\Http\Requests\StoreUserRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateUserRequest;
use VentureDrake\LaravelCrm\Models\Role;
use VentureDrake\LaravelCrm\Models\Team;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (config('laravel-crm.teams')) {
            if (auth()->user()->currentTeam) {
                $users = auth()->user()->currentTeam->allUsers();

                if ($users->count() > 30) {
                    $users = $users->paginate(30);
                }
            }
        } else {
            if (User::all()->count() < 30) {
                $users = User::latest()->get();
            } else {
                $users = User::latest()->paginate(30);
            }
        }
        
        return view('laravel-crm::users.index', [
            'users' => $users ?? [],
        ]);
    }

    /**
     * Show the form for inviting a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function invite()
    {
        return view('laravel-crm::users.invite');
    }

    /**
     * Send invite
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendInvite(InviteUserRequest $request)
    {
        flash(ucfirst(trans('laravel-crm::lang.user_invitation_sent')))->success()->important();

        return redirect(route('laravel-crm.users.index'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $teams = Team::orderBy('name', 'ASC')->get();
        
        return view('laravel-crm::users.create', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::forceCreate([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'crm_access' => (($request->crm_access == 'on') ? 1 : 0),
        ]);
        
        if ($request->role) {
            if ($role = Role::find($request->role)) {
                if ($removeRole = $user->roles()->where('crm_role', 1)->first()) { // THIS COULD BE A BUG
                    $user->removeRole($removeRole);
                }
                
                $user->assignRole($role);
            }
        }

        if (config('laravel-crm.teams')) {
            if ($team = auth()->user()->currentTeam) {
                DB::table('team_user')->insert([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'editor', // Default Jetstream role
                ]);
                
                $user->forceFill([
                    'current_team_id' => $team->id,
                ])->save();
            };
        }

        if ($request->user_teams) {
            $user->crmTeams()->sync($request->user_teams);
        } else {
            $user->crmTeams()->sync([]);
        }
        
        flash(ucfirst(trans('laravel-crm::lang.user_stored')))->success()->important();

        return redirect(route('laravel-crm.users.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('laravel-crm::users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $teams = Team::orderBy('name', 'ASC')->get();
        
        return view('laravel-crm::users.edit', [
            'user' => $user,
            'teams' => $teams,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->forceFill([
            'name' => $request->name,
            'email' => $request->email,
            'crm_access' => (($request->crm_access == 'on') ? 1 : 0),
        ])->save();

        if ($request->role) {
            if ($role = Role::find($request->role)) {
                if ($removeRole = $user->roles()->where('crm_role', 1)->first()) {
                    $user->removeRole($removeRole);
                }

                $user->assignRole($role);
            }
        }

        if ($request->user_teams) {
            $user->crmTeams()->sync($request->user_teams);
        } else {
            $user->crmTeams()->sync([]);
        }
        
        flash(ucfirst(trans('laravel-crm::lang.user_updated')))->success()->important();

        return redirect(route('laravel-crm.users.show', $user));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        flash(ucfirst(trans('laravel-crm::lang.user_deleted')))->success()->important();

        return redirect(route('laravel-crm.users.index'));
    }
}
