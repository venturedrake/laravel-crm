<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use DB;
use Illuminate\Support\Facades\Hash;
use VentureDrake\LaravelCrm\Http\Requests\InviteUserRequest;
use VentureDrake\LaravelCrm\Http\Requests\StoreUserRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateUserRequest;
use VentureDrake\LaravelCrm\Models\Role;

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
        return view('laravel-crm::users.create');
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

        $roles = [];
        if ($request->role) {
            if ($role = Role::find($request->role)) {
                $roles = [$role->name];
            }
        }

        $user->syncRoles($roles);

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
        return view('laravel-crm::users.edit', [
            'user' => $user,
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

        $roles = [];
        if ($request->role) {
            if ($role = Role::find($request->role)) {
                $roles = [$role->name];
            }
        }

        $user->syncRoles($roles);
        
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
