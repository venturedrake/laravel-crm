<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StorePersonRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePersonRequest;
use VentureDrake\LaravelCrm\Models\Contact;
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
    public function index(Request $request)
    {
        Person::resetSearchValue($request);
        $params = Person::filters($request);
        $people = Person::filter($params);

        // This is  not the best, will refactor. Problem with trying to sort encryoted fields
        if (request()->only(['sort', 'direction']) && config('laravel-crm.encrypt_db_fields')) {
            $people = $people->get();

            foreach ($people as $key => $person) {
                $people[$key]->first_name_decrypted = $person->first_name;
                $people[$key]->last_name_decrypted = $person->last_name;
                $people[$key]->organisation_name_decrypted = $person->organisation->name ?? null;
            }

            $sortField = Str::replace('.', '_', request()->only(['sort', 'direction'])['sort']).'_decrypted';

            if (request()->only(['sort', 'direction'])['direction'] == 'asc') {
                $people = $people->sortBy($sortField);
            } else {
                $people = $people->sortByDesc($sortField);
            }

            if ($people->count() > 30) {
                $people = $people->paginate(30);
            }
        } else {
            if ($people->count() < 30) {
                $people = $people->sortable(['created_at' => 'desc'])->get();
            } else {
                $people = $people->sortable(['created_at' => 'desc'])->paginate(30);
            }
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
        $organisation = $person->organisation;
        if ($organisation) {
            $organisationAddress = $organisation->getPrimaryAddress();
        }

        return view('laravel-crm::people.show', [
            'person' => $person,
            'emails' => $person->emails,
            'phones' => $person->phones,
            'addresses' => $person->addresses,
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
        return view('laravel-crm::people.edit', [
            'person' => $person,
            'emails' => $person->emails,
            'phones' => $person->phones,
            'addresses' => $person->addresses,
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
        } elseif (trim($request->organisation_name) == '' && $person->organisation) {
            $person->organisation()->dissociate();
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
        foreach(Contact::where([
            'entityable_type' => $person->getMorphClass(),
            'entityable_id' => $person->id
        ])->get() as $contact) {
            $contact->delete();
        }

        $person->delete();

        flash(ucfirst(trans('laravel-crm::lang.person_deleted')))->success()->important();

        return redirect(route('laravel-crm.people.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Person::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.people.index'));
        }

        $params = Person::filters($request, 'search');

        $people = Person::filter($params)->get()->filter(function ($record) use ($searchValue) {
            foreach ($record->getSearchable() as $field) {
                if (Str::contains(strtolower($record->{$field}), strtolower($searchValue))) {
                    return $record;
                }
            }
        });

        return view('laravel-crm::people.index', [
            'people' => $people,
            'searchValue' => $searchValue ?? null,
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
