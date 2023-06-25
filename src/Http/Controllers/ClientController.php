<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\StoreClientRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateClientRequest;
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
        return view('laravel-crm::clients.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreClientRequest $request)
    {
        $client = Client::create([
             'name' => $request->name,
             'user_owner_id' => $request->user_owner_id,
         ]);

        $client->labels()->sync($request->labels ?? []);

        flash(ucfirst(trans('laravel-crm::lang.client_stored')))->success()->important();

        return redirect(route('laravel-crm.clients.index'));
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
        return view('laravel-crm::clients.edit', [
             'client' => $client,
         ]);
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
        $client->update([
             'name' => $request->name,
             'user_owner_id' => $request->user_owner_id,
         ]);

        $client->labels()->sync($request->labels ?? []);

        flash(ucfirst(trans('laravel-crm::lang.client_updated')))->success()->important();

        return redirect(route('laravel-crm.clients.show', $client));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        $client->delete();

        flash(ucfirst(trans('laravel-crm::lang.client_deleted')))->success()->important();

        return redirect(route('laravel-crm.clients.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Client::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.clients.index'));
        }

        $params = Client::filters($request, 'search');

        $clients = Client::filter($params)->get()->filter(function ($record) use ($searchValue) {
            foreach ($record->getSearchable() as $field) {
                if (Str::contains(strtolower($record->{$field}), strtolower($searchValue))) {
                    return $record;
                }
            }
        });

        return view('laravel-crm::clients.index', [
            'clients' => $clients,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    public function autocomplete(Client $client)
    {
        /*$email = $person->getPrimaryEmail();
        $phone = $person->getPrimaryPhone();

        return response()->json([
            'email' => $email->address ?? null,
            'email_type' => $email->type ?? null,
            'phone' => $phone->number ?? null,
            'phone_type' => $phone->type ?? null,
        ]);*/
    }
}
