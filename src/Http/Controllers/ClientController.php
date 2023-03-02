<?php
namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Client;

class ClientController extends Controller
{
    public function __construct()
    {
        //
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Client::resetSearchValue($request);
        $params = Client::filters($request);
        $clients = Client::filter($params);

        // This is  not the best, will refactor. Problem with trying to sort encryoted fields
        if (request()->only(['sort', 'direction']) && config('laravel-crm.encrypt_db_fields')) {
            $clients = $clients->get();

            foreach ($clients as $key => $client) {
                $clients[$key]->name_decrypted = $client->name;
            }

            $sortField = Str::replace('.', '_', request()->only(['sort', 'direction'])['sort']).'_decrypted';

            if (request()->only(['sort', 'direction'])['direction'] == 'asc') {
                $clients = $clients->sortBy($sortField);
            } else {
                $clients = $clients->sortByDesc($sortField);
            }

            if ($clients->count() > 30) {
                $clients = $clients->paginate(30);
            }
        } else {
            if ($clients->count() < 30) {
                $clients = $clients->sortable(['created_at' => 'desc'])->get();
            } else {
                $clients = $clients->sortable(['created_at' => 'desc'])->paginate(30);
            }
        }
        
        return view('laravel-crm::clients.index', [
            'clients' => $clients,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        /*switch ($request->model) {
            case "organisation":
                $organisation = Client::find($request->id);

                break;
        }

        return view('laravel-crm::people.create', [
            'organisation' => $organisation ?? null,
        ]);*/
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreClientRequest $request)
    {
        /* $person = $this->personService->create($request);

         $person->labels()->sync($request->labels ?? []);

         if ($request->organisation_name) {
             if (! $request->organisation_id) {
                 $organisation = Client::create([
                     'external_id' => Uuid::uuid4()->toString(),
                     'name' => $request->organisation_name,
                     'user_owner_id' => $request->user_owner_id,
                 ]);
                 $person->organisation()->associate($organisation);
             } else {
                 $person->organisation()->associate(Client::find($request->organisation_id));
             }
             $person->save();
         }

         flash(ucfirst(trans('laravel-crm::lang.person_stored')))->success()->important();

         return redirect(route('laravel-crm.people.index'));*/
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Client $client)
    {
        return view('laravel-crm::clients.show', [
             'client' => $client,
         ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
        /* return view('laravel-crm::people.edit', [
             'person' => $person,
             'emails' => $person->emails,
             'phones' => $person->phones,
             'addresses' => $person->addresses,
         ]);*/
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        /* $person = $this->personService->update($person, $request);

         $person->labels()->sync($request->labels ?? []);

         if ($request->organisation_name) {
             if (! $request->organisation_id) {
                 $organisation = Client::create([
                     'external_id' => Uuid::uuid4()->toString(),
                     'name' => $request->organisation_name,
                     'user_owner_id' => $request->user_owner_id,
                 ]);
                 $person->organisation()->associate($organisation);
             } else {
                 $person->organisation()->associate(Client::find($request->organisation_id));
             }
             $person->save();
         } elseif (trim($request->organisation_name) == '' && $person->organisation) {
             $person->organisation()->dissociate();
             $person->save();
         }

         flash(ucfirst(trans('laravel-crm::lang.person_updated')))->success()->important();

         return redirect(route('laravel-crm.people.show', $person));*/
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        /* $person->delete();

         flash(ucfirst(trans('laravel-crm::lang.person_deleted')))->success()->important();

         return redirect(route('laravel-crm.people.index'));*/
    }

    public function search(Request $request)
    {
        /* $searchValue = Person::searchValue($request);

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
         ]);*/
    }

    public function autocomplete(Client $client)
    {
        /* $email = $person->getPrimaryEmail();
         $phone = $person->getPrimaryPhone();

         return response()->json([
             'email' => $email->address ?? null,
             'email_type' => $email->type ?? null,
             'phone' => $phone->number ?? null,
             'phone_type' => $phone->type ?? null,
         ]);*/
    }
}
