<?php
namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
    public function create(Request $request)
    {
        switch ($request->model) {
            case "organisation":
                $organisation = Organisation::find($request->id);

                break;
        }
        
        return view('laravel-crm::people.create', [
            'organisation' => $organisation ?? null,
        ]);
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
        
        flash(ucfirst(trans('laravel-crm::lang.person_stored')))->success()->important();

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
        $address = $person->getPrimaryAddress();
        
        return view('laravel-crm::people.edit', [
            'person' => $person,
            'emails' => $person->emails,
            'phones' => $person->phones,
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
        
        flash(ucfirst(trans('laravel-crm::lang.person_updated')))->success()->important();

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

        flash(ucfirst(trans('laravel-crm::lang.person_deleted')))->success()->important();

        return redirect(route('laravel-crm.people.index'));
    }

    public function search(Request $request)
    {
        $searchValue = $request->search;

        $people = Person::all()->filter(function ($record) use ($searchValue) {
            foreach ($record->getSearchable() as $field) {
                if (Str::contains($record->{$field}, $searchValue)) {
                    return $record;
                }
            }
        });

        return view('laravel-crm::people.index', [
            'people' => $people,
        ]);
    }

    public function autocomplete(Person $person)
    {
        $email = $person->getPrimaryEmail();
        $phone = $person->getPrimaryPhone();

        return response()->json([
            'email' => $email->address ?? null,
            'email_type' => $email->type ?? null,
            'phone' => $phone->number ?? null,
            'phone_type' => $phone->type ?? null,
        ]);
    }
}
