<?php
namespace VentureDrake\LaravelCrm\Http\Controllers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StorePersonRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePersonRequest;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Person::all()->count() < 30) {
            $people = Person::latest()->get();
        } else {
            $people = Person::latest()->paginate(30);
        }
        
        return view('laravel-crm::people.index', [
            'people' => $people,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::people.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePersonRequest $request)
    {
        $person = Person::create([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $request->title,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'birthday' => $request->birthday,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        if ($request->phone) {
            $person->phones()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => $request->phone,
                'type' => $request->phone_type,
                'primary' => 1,
            ]);
        }

        if ($request->email) {
            $person->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => 1,
            ]);
        }

        $person->addresses()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'line1' => $request->line1,
            'line2' => $request->line2,
            'line3' => $request->line3,
            'suburb' => $request->suburb,
            'state' => $request->state,
            'code' => $request->code,
            'country' => $request->country,
            'primary' => 1,
        ]);
        
        if ($request->organisation_name) {
            if (! $request->organisation_id) {
                $organisation = Organisation::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'name' => $request->organisation_name,
                    'user_owner_id' => $request->user_owner_id,
                ]);
                $person->organisation()->associate($organisation);
            } else {
                $person->organisation()->associate(Organisation::find($request->organisation_id));
            }
            $person->save();
        }
        
        flash('Person stored')->success()->important();

        return redirect(route('laravel-crm.people.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Person $person)
    {
        $email = $person->getPrimaryEmail();
        $phone = $person->getPrimaryPhone();
        $address = $person->getPrimaryAddress();
        
        return view('laravel-crm::people.show', [
            'person' => $person,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation' => $organisation ?? null,
            'organisation_address' => $organisation_address ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Person $person)
    {
        $email = $person->getPrimaryEmail();
        $phone = $person->getPrimaryPhone();
        $address = $person->getPrimaryAddress();
        
        return view('laravel-crm::people.edit', [
            'person' => $person,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePersonRequest $request, Person $person)
    {
        $person->update([
            'title' => $request->title,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'birthday' => $request->birthday,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $email = $person->getPrimaryEmail();
        $phone = $person->getPrimaryPhone();
        $address = $person->getPrimaryAddress();

        if ($request->phone && $phone) {
            $phone->update([
                'number' => $request->phone,
                'type' => $request->phone_type,
            ]);
        } elseif ($request->phone) {
            $person->phones()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => $request->phone,
                'type' => $request->phone_type,
                'primary' => 1,
            ]);
        } elseif ($phone) {
            $phone->delete();
        }

        if ($request->email && $email) {
            $email->update([
                'address' => $request->email,
                'type' => $request->email_type,
            ]);
        } elseif ($request->email) {
            $person->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => 1,
            ]);
        } elseif ($email) {
            $email->delete();
        }

        if ($address) {
            $address->update([
                'line1' => $request->line1,
                'line2' => $request->line2,
                'line3' => $request->line3,
                'suburb' => $request->suburb,
                'state' => $request->state,
                'code' => $request->code,
                'country' => $request->country,
            ]);
        } elseif ($request->email) {
            $person->addresses()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'line1' => $request->line1,
                'line2' => $request->line2,
                'line3' => $request->line3,
                'suburb' => $request->suburb,
                'state' => $request->state,
                'code' => $request->code,
                'country' => $request->country,
                'primary' => 1,
            ]);
        }

        if ($request->organisation_name) {
            if (! $request->organisation_id) {
                $organisation = Organisation::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'name' => $request->organisation_name,
                    'user_owner_id' => $request->user_owner_id,
                ]);
                $person->organisation()->associate($organisation);
            } else {
                $person->organisation()->associate(Organisation::find($request->organisation_id));
            }
            $person->save();
        }
        
        flash('Person updated')->success()->important();

        return redirect(route('laravel-crm.people.show', $person));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Person $person)
    {
        $person->delete();

        flash('Person deleted')->success()->important();

        return redirect(route('laravel-crm.people.index'));
    }
}
