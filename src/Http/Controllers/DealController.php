<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\StoreDealRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateDealRequest;
use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrm\Services\OrganisationService;
use VentureDrake\LaravelCrm\Services\PersonService;

class DealController extends Controller
{
    /**
     * @var DealService
     */
    private $dealService;

    /**
     * @var PersonService
     */
    private $personService;

    /**
     * @var OrganisationService
     */
    private $organisationService;

    public function __construct(DealService $dealService, PersonService $personService, OrganisationService $organisationService)
    {
        $this->dealService = $dealService;
        $this->personService = $personService;
        $this->organisationService = $organisationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Deal::resetSearchValue($request);
        $params = Deal::filters($request);

        if (Deal::filter($params)->get()->count() < 30) {
            $deals = Deal::filter($params)->latest()->get();
        } else {
            $deals = Deal::filter($params)->latest()->paginate(30);
        }

        return view('laravel-crm::deals.index', [
            'deals' => $deals,
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
            case 'client':
                $client = Client::find($request->id);

                break;

            case 'organisation':
                $organisation = Organisation::find($request->id);

                break;

            case 'person':
                $person = Person::find($request->id);

                break;
        }

        return view('laravel-crm::deals.create', [
            'client' => $client ?? null,
            'organisation' => $organisation ?? null,
            'person' => $person ?? null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDealRequest $request)
    {
        if ($request->person_name && ! $request->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($request->person_id) {
            $person = Person::find($request->person_id);
        }

        if ($request->organisation_name && ! $request->organisation_id) {
            $organisation = $this->organisationService->createFromRelated($request);
        } elseif ($request->organisation_id) {
            $organisation = Organisation::find($request->organisation_id);
        }

        if ($request->client_name && ! $request->client_id) {
            $client = Client::create([
                'name' => $request->client_name,
                'user_owner_id' => $request->user_owner_id,
            ]);
        } elseif ($request->client_id) {
            $client = Client::find($request->client_id);
        }

        if (isset($client)) {
            if (isset($organisation)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $organisation->getMorphClass(),
                    'entityable_id' => $organisation->id,
                ]);
            }

            if (isset($person)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $this->dealService->create($request, $person ?? null, $organisation ?? null, $client ?? null);

        flash(ucfirst(trans('laravel-crm::lang.deal_stored')))->success()->important();

        return redirect(route('laravel-crm.deals.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Deal $deal)
    {
        if ($deal->person) {
            $email = $deal->person->getPrimaryEmail();
            $phone = $deal->person->getPrimaryPhone();
            $address = $deal->person->getPrimaryAddress();
        }

        if ($deal->organisation) {
            $organisation_address = $deal->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::deals.show', [
            'deal' => $deal,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Deal $deal)
    {
        if ($deal->person) {
            $email = $deal->person->getPrimaryEmail();
            $phone = $deal->person->getPrimaryPhone();
        }

        if ($deal->organisation) {
            $address = $deal->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::deals.edit', [
            'deal' => $deal,
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
    public function update(UpdateDealRequest $request, Deal $deal)
    {
        if ($request->person_name && ! $request->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($request->person_id) {
            $person = Person::find($request->person_id);
        }

        if ($request->organisation_name && ! $request->organisation_id) {
            $organisation = $this->organisationService->createFromRelated($request);
        } elseif ($request->organisation_id) {
            $organisation = Organisation::find($request->organisation_id);
        }

        if ($request->client_name && ! $request->client_id) {
            $client = Client::create([
                'name' => $request->client_name,
                'user_owner_id' => $request->user_owner_id,
            ]);
        } elseif ($request->client_id) {
            $client = Client::find($request->client_id);
        }

        if (isset($client)) {
            if (isset($organisation)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $organisation->getMorphClass(),
                    'entityable_id' => $organisation->id,
                ]);
            }

            if (isset($person)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $deal = $this->dealService->update($request, $deal, $person ?? null, $organisation ?? null, $client ?? null);

        flash(ucfirst(trans('laravel-crm::lang.deal_updated')))->success()->important();

        return redirect(route('laravel-crm.deals.show', $deal));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Deal $deal)
    {
        $deal->delete();

        flash(ucfirst(trans('laravel-crm::lang.deal_deleted')))->success()->important();

        return redirect(route('laravel-crm.deals.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Deal::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.deals.index'));
        }

        $params = Deal::filters($request, 'search');

        $deals = Deal::filter($params)
            ->select(
                config('laravel-crm.db_table_prefix').'deals.*',
                config('laravel-crm.db_table_prefix').'people.first_name',
                config('laravel-crm.db_table_prefix').'people.middle_name',
                config('laravel-crm.db_table_prefix').'people.last_name',
                config('laravel-crm.db_table_prefix').'people.maiden_name',
                config('laravel-crm.db_table_prefix').'organisations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'deals.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organisations', config('laravel-crm.db_table_prefix').'deals.organisation_id', '=', config('laravel-crm.db_table_prefix').'organisations.id')
            ->get()
            ->filter(function ($record) use ($searchValue) {
                foreach ($record->getSearchable() as $field) {
                    if (Str::contains($field, '.')) {
                        $field = explode('.', $field);
                        if ($record->{$field[1]} && $descryptedField = decrypt($record->{$field[1]})) {
                            if (Str::contains(strtolower($descryptedField), strtolower($searchValue))) {
                                return $record;
                            }
                        }
                    } elseif ($record->{$field}) {
                        if (Str::contains(strtolower($record->{$field}), strtolower($searchValue))) {
                            return $record;
                        }
                    }
                }
            });

        return view('laravel-crm::deals.index', [
            'deals' => $deals,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function won(Deal $deal)
    {
        $deal->update([
            'closed_status' => 'won',
            'closed_at' => Carbon::now(),
        ]);

        flash(ucfirst(trans('laravel-crm::lang.deal_won')))->success()->important();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function lost(Deal $deal)
    {
        $deal->update([
            'closed_status' => 'lost',
            'closed_at' => Carbon::now(),
        ]);

        flash(ucfirst(trans('laravel-crm::lang.deal_lost')))->success()->important();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reopen(Deal $deal)
    {
        $deal->update([
            'closed_status' => null,
            'closed_at' => null,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.deal_reopened')))->success()->important();

        return back();
    }
}
