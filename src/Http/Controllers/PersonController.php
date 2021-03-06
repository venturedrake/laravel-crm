<?php
namespace VentureDrake\LaravelCrm\Http\Controllers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StorePersonRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePersonRequest;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\PersonService;

class PersonController extends Controller
{
    /**
     * @var PersonService
     */
    private $personService;

    public function __construct(PersonService $personService)
    {
        $this->personService = $personService;
    }
    
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
        $person = $this->personService->create($request);

        $person->labels()->sync($request->labels ?? []);
        
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
        $organisation = $person->organisation;
        if ($organisation) {
            $organisationAddress = $organisation->getPrimaryAddress();
        }
        
        return view('laravel-crm::people.show', [
            'person' => $person,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation' => $organisation ?? null,
            'organisation_address' => $organisationAddress ?? null,
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
        $person = $this->personService->update($person, $request);

        $person->labels()->sync($request->labels ?? []);

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
