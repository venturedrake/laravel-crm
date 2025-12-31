<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\StoreOrganizationRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateOrganizationRequest;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Services\OrganizationService;

class OrganizationController extends Controller
{
    /**
     * @var OrganizationService
     */
    private $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /*Organization::resetSearchValue($request);
        $params = Organization::filters($request);
        $organizations = Organization::filter($params);

        // This is  not the best, will refactor. Problem with trying to sort encryoted fields
        if (request()->only(['sort', 'direction']) && config('laravel-crm.encrypt_db_fields')) {
            $organizations = $organizations->get();

            foreach ($organizations as $key => $organization) {
                $organizations[$key]->name_decrypted = $organization->name;
            }

            $sortField = Str::replace('.', '_', request()->only(['sort', 'direction'])['sort']).'_decrypted';

            if (request()->only(['sort', 'direction'])['direction'] == 'asc') {
                $organizations = $organizations->sortBy($sortField);
            } else {
                $organizations = $organizations->sortByDesc($sortField);
            }

            if ($organizations->count() > 30) {
                $organizations = $organizations->paginate(30);
            }
        } else {
            if ($organizations->count() < 30) {
                $organizations = $organizations->sortable(['created_at' => 'desc'])->get();
            } else {
                $organizations = $organizations->sortable(['created_at' => 'desc'])->paginate(30);
            }
        }*/

        return view('laravel-crm::organizations.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::organizations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrganizationRequest $request)
    {
        $organization = $this->organizationService->create($request);

        $organization->labels()->sync($request->labels ?? []);

        flash(ucfirst(trans('laravel-crm::lang.organization_stored')))->success()->important();

        return redirect(route('laravel-crm.organizations.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Organization $organization)
    {
        return view('laravel-crm::organizations.show', [
            'organization' => $organization,
            'emails' => $organization->emails,
            'phones' => $organization->phones,
            'addresses' => $organization->addresses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Organization $organization)
    {
        return view('laravel-crm::organizations.edit', [
            'organization' => $organization,
            'emails' => $organization->emails,
            'phones' => $organization->phones,
            'addresses' => $organization->addresses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $this->organizationService->update($organization, $request);

        $organization->labels()->sync($request->labels ?? []);

        flash(ucfirst(trans('laravel-crm::lang.organization_updated')))->success()->important();

        return redirect(route('laravel-crm.organizations.show', $organization));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Organization $organization)
    {
        foreach (Contact::where([
            'entityable_type' => $organization->getMorphClass(),
            'entityable_id' => $organization->id,
        ])->get() as $contact) {
            $contact->delete();
        }

        $organization->delete();

        flash(ucfirst(trans('laravel-crm::lang.organization_deleted')))->success()->important();

        return redirect(route('laravel-crm.organizations.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Organization::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.organizations.index'));
        }

        $params = Organization::filters($request, 'search');

        $organizations = Organization::filter($params)->get()->filter(function ($record) use ($searchValue) {
            foreach ($record->getSearchable() as $field) {
                if (Str::contains(strtolower($record->{$field}), strtolower($searchValue))) {
                    return $record;
                }
            }
        });

        return view('laravel-crm::organizations.index', [
            'organizations' => $organizations,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    public function autocomplete(Organization $organization)
    {
        $address = $organization->getPrimaryAddress();

        return response()->json([
            'address_line1' => $address->line1 ?? null,
            'address_line2' => $address->line2 ?? null,
            'address_line3' => $address->line3 ?? null,
            'address_city' => $address->city ?? null,
            'address_state' => $address->state ?? null,
            'address_code' => $address->code ?? null,
            'address_country' => $address->country ?? null,
        ]);
    }
}
