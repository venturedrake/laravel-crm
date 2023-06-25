<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\StoreTeamRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateTeamRequest;
use VentureDrake\LaravelCrm\Models\Team;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Team::all()->count() < 30) {
            $teams = Team::latest()->get();
        } else {
            $teams = Team::latest()->paginate(30);
        }

        return view('laravel-crm::teams.index', [
            'teams' => $teams,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (config('laravel-crm.teams')) {
            if (auth()->user()->currentTeam) {
                $users = auth()->user()->currentTeam->allUsers();
            }
        } else {
            $users = User::orderBy('name', 'ASC')->get();
        }

        return view('laravel-crm::teams.create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTeamRequest $request)
    {
        $team = Team::create([
            'name' => $request->name,
            'user_id' => auth()->user()->id,
        ]);

        if ($request->team_users) {
            $team->users()->sync($request->team_users);
        } else {
            $team->users()->sync([]);
        }

        flash(ucfirst(trans('laravel-crm::lang.team_stored')))->success()->important();

        return redirect(route('laravel-crm.teams.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Team $team)
    {
        return view('laravel-crm::teams.show', [
            'team' => $team,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Team $team)
    {
        if (config('laravel-crm.teams')) {
            if (auth()->user()->currentTeam) {
                $users = auth()->user()->currentTeam->allUsers();
            }
        } else {
            $users = User::orderBy('name', 'ASC')->get();
        }

        return view('laravel-crm::teams.edit', [
            'team' => $team,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update([
            'name' => $request->name,
        ]);

        if ($request->team_users) {
            $team->users()->sync($request->team_users);
        } else {
            $team->users()->sync([]);
        }

        flash(ucfirst(trans('laravel-crm::lang.team_updated')))->success()->important();

        return redirect(route('laravel-crm.teams.show', $team));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Team $team)
    {
        $team->delete();

        flash(ucfirst(trans('laravel-crm::lang.team_deleted')))->success()->important();

        return redirect(route('laravel-crm.teams.index'));
    }
}
