<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\StoreDealRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateDealRequest;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
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
     * @var OrganizationService
     */
    private $organizationService;

    public function __construct(DealService $dealService, PersonService $personService, OrganizationService $organizationService)
    {
        $this->dealService = $dealService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $viewSetting = auth()->user()->crmSettings()->where('name', 'view_deals')->first();

        if (! $viewSetting) {
            auth()->user()->crmSettings()->create([
                'name' => 'view_deals',
                'value' => 'list',
            ]);
        } elseif ($viewSetting->value == 'board') {
            return redirect(route('laravel-crm.deals.board'));
        }

        Deal::resetSearchValue($request);
        $params = Deal::filters($request);

        if (Deal::filter($params)->get()->count() < 30) {
            $deals = Deal::filter($params)->latest()->get();
        } else {
            $deals = Deal::filter($params)->latest()->paginate(30);
        }

        return view('laravel-crm::deals.index', [
            'deals' => $deals,
            'viewSetting' => $viewSetting->value ?? null,
            'pipeline' => Pipeline::where('model', get_class(new Deal))->first(),
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
                $client = Customer::find($request->id);

                break;

            case 'organization':
                $organization = Organization::find($request->id);

                break;

            case 'person':
                $person = Person::find($request->id);

                break;
        }

        return view('laravel-crm::deals.create', [
            'client' => $client ?? null,
            'organization' => $organization ?? null,
            'person' => $person ?? null,
            'pipeline' => Pipeline::where('model', get_class(new Deal))->first(),
            'stage' => $request->stage ?? null,
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

        if ($request->organization_name && ! $request->organization_id) {
            $organization = $this->organizationService->createFromRelated($request);
        } elseif ($request->organization_id) {
            $organization = Organization::find($request->organization_id);
        }

        if ($request->client_name && ! $request->client_id) {
            $client = Customer::create([
                'name' => $request->client_name,
                'user_owner_id' => $request->user_owner_id,
            ]);
        } elseif ($request->client_id) {
            $client = Customer::find($request->client_id);
        }

        if (isset($client)) {
            if (isset($organization)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $organization->getMorphClass(),
                    'entityable_id' => $organization->id,
                ]);
            }

            if (isset($person)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $this->dealService->create($request, $person ?? null, $organization ?? null, $client ?? null);

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

        if ($deal->organization) {
            $organization_address = $deal->organization->getPrimaryAddress();
        }

        return view('laravel-crm::deals.show', [
            'deal' => $deal,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organization_address' => $organization_address ?? null,
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

        if ($deal->organization) {
            $address = $deal->organization->getPrimaryAddress();
        }

        return view('laravel-crm::deals.edit', [
            'deal' => $deal,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'pipeline' => Pipeline::where('model', get_class(new Deal))->first(),
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

        if ($request->organization_name && ! $request->organization_id) {
            $organization = $this->organizationService->createFromRelated($request);
        } elseif ($request->organization_id) {
            $organization = Organization::find($request->organization_id);
        }

        if ($request->client_name && ! $request->client_id) {
            $client = Customer::create([
                'name' => $request->client_name,
                'user_owner_id' => $request->user_owner_id,
            ]);
        } elseif ($request->client_id) {
            $client = Customer::find($request->client_id);
        }

        if (isset($client)) {
            if (isset($organization)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $organization->getMorphClass(),
                    'entityable_id' => $organization->id,
                ]);
            }

            if (isset($person)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $deal = $this->dealService->update($request, $deal, $person ?? null, $organization ?? null, $client ?? null);

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
        $viewSetting = auth()->user()->crmSettings()->where('name', 'view_deals')->first();

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
                config('laravel-crm.db_table_prefix').'organizations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'deals.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'deals.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->get()
            ->filter(function ($record) use ($searchValue) {
                foreach ($record->getSearchable() as $field) {
                    if (Str::contains($field, '.')) {
                        $field = explode('.', $field);

                        if (config('laravel-crm.encrypt_db_fields')) {
                            try {
                                $relatedField = decrypt($record->{$field[1]});
                            } catch (DecryptException $e) {
                                $relatedField = $record->{$field[1]};
                            }
                        } else {
                            $relatedField = $record->{$field[1]};
                        }

                        if ($record->{$field[1]} && $relatedField) {
                            if (Str::contains(strtolower($relatedField), strtolower($searchValue))) {
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

        if ($viewSetting->value === 'board') {
            return view('laravel-crm::deals.board', [
                'deals' => $deals,
                'searchValue' => $searchValue ?? null,
                'viewSetting' => $viewSetting->value ?? null,
            ]);
        } else {
            return view('laravel-crm::deals.index', [
                'deals' => $deals,
                'searchValue' => $searchValue ?? null,
                'viewSetting' => $viewSetting->value ?? null,
                'pipeline' => Pipeline::where('model', get_class(new Deal))->first(),
            ]);
        }
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

    public function list(Request $request)
    {
        auth()->user()->crmSettings()->updateOrCreate([
            'name' => 'view_deals',
        ], [
            'value' => 'list',
        ]);

        return redirect(route('laravel-crm.deals.index'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function board(Request $request)
    {
        $viewSetting = auth()->user()->crmSettings()->where('name', 'view_deals')->first();

        auth()->user()->crmSettings()->updateOrCreate([
            'name' => 'view_deals',
        ], [
            'value' => 'board',
        ]);

        Deal::resetSearchValue($request);
        $params = Deal::filters($request);

        $deals = Deal::filter($params)->latest()->get();

        return view('laravel-crm::deals.board', [
            'deals' => $deals,
            'viewSetting' => $viewSetting->value ?? null,
        ]);
    }
}
