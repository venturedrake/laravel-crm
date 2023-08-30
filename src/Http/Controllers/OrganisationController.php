<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\StoreOrganisationRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateOrganisationRequest;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Services\OrganisationService;

class OrganisationController extends Controller
{
    /**
     * @var OrganisationService
     */
    private $organisationService;

    public function __construct(OrganisationService $organisationService)
    {
        $this->organisationService = $organisationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Organisation::resetSearchValue($request);
        $params = Organisation::filters($request);
        $organisations = Organisation::filter($params);

        // This is  not the best, will refactor. Problem with trying to sort encryoted fields
        if (request()->only(['sort', 'direction']) && config('laravel-crm.encrypt_db_fields')) {
            $organisations = $organisations->get();

            foreach ($organisations as $key => $organisation) {
                $organisations[$key]->name_decrypted = $organisation->name;
            }

            $sortField = Str::replace('.', '_', request()->only(['sort', 'direction'])['sort']).'_decrypted';

            if (request()->only(['sort', 'direction'])['direction'] == 'asc') {
                $organisations = $organisations->sortBy($sortField);
            } else {
                $organisations = $organisations->sortByDesc($sortField);
            }

            if ($organisations->count() > 30) {
                $organisations = $organisations->paginate(30);
            }
        } else {
            if ($organisations->count() < 30) {
                $organisations = $organisations->sortable(['created_at' => 'desc'])->get();
            } else {
                $organisations = $organisations->sortable(['created_at' => 'desc'])->paginate(30);
            }
        }

        return view('laravel-crm::organisations.index', [
            'organisations' => $organisations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::organisations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrganisationRequest $request)
    {
        $organisation = $this->organisationService->create($request);

        $organisation->labels()->sync($request->labels ?? []);

        flash(ucfirst(trans('laravel-crm::lang.organization_stored')))->success()->important();

        return redirect(route('laravel-crm.organisations.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Organisation $organisation)
    {
        return view('laravel-crm::organisations.show', [
            'organisation' => $organisation,
            'emails' => $organisation->emails,
            'phones' => $organisation->phones,
            'addresses' => $organisation->addresses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Organisation $organisation)
    {
        return view('laravel-crm::organisations.edit', [
            'organisation' => $organisation,
            'emails' => $organisation->emails,
            'phones' => $organisation->phones,
            'addresses' => $organisation->addresses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrganisationRequest $request, Organisation $organisation)
    {
        $this->organisationService->update($organisation, $request);

        $organisation->labels()->sync($request->labels ?? []);

        flash(ucfirst(trans('laravel-crm::lang.organization_updated')))->success()->important();

        return redirect(route('laravel-crm.organisations.show', $organisation));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Organisation $organisation)
    {
        foreach(Contact::where([
            'entityable_type' => $organisation->getMorphClass(),
            'entityable_id' => $organisation->id
        ])->get() as $contact) {
            $contact->delete();
        }

        $organisation->delete();

        flash(ucfirst(trans('laravel-crm::lang.organization_deleted')))->success()->important();

        return redirect(route('laravel-crm.organisations.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Organisation::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.organisations.index'));
        }

        $params = Organisation::filters($request, 'search');

        $organisations = Organisation::filter($params)->get()->filter(function ($record) use ($searchValue) {
            foreach ($record->getSearchable() as $field) {
                if (Str::contains(strtolower($record->{$field}), strtolower($searchValue))) {
                    return $record;
                }
            }
        });

        return view('laravel-crm::organisations.index', [
            'organisations' => $organisations,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    public function autocomplete(Organisation $organisation)
    {
        $address = $organisation->getPrimaryAddress();

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
