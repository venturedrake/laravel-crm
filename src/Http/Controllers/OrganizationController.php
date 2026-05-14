<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     * @return Response
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
     * @return Response
     */
    public function create()
    {
        return view('laravel-crm::organizations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
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
     * @return Response
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
     * @return Response
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
     * @param  Request  $request
     * @param  int  $id
     * @return Response
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
     * @return Response
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

    /**
     * Show the bulk import form.
     */
    public function import()
    {
        return view('laravel-crm::organizations.import');
    }

    /**
     * Parse an uploaded CSV and store the result in the session.
     */
    public function parseImport(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return back()->withErrors(['csv_file' => ucfirst(__('laravel-crm::lang.import_file_error'))]);
        }

        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $required = ['name'];
        $missing = array_diff($required, $header);

        if (! empty($missing)) {
            fclose($handle);

            return back()->withErrors([
                'csv_file' => ucfirst(__('laravel-crm::lang.import_missing_columns')).': '.implode(', ', $missing),
            ]);
        }

        $rows = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            }

            $data = array_combine($header, $row);
            $rowErrors = [];

            if (empty(trim($data['name'] ?? ''))) {
                $rowErrors[] = __('laravel-crm::lang.import_organization_name_required');
            }

            $email = trim($data['email'] ?? '');
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = __('laravel-crm::lang.import_email_invalid');
            }

            $rows[] = [
                'row' => $rowNumber,
                'name' => trim($data['name'] ?? ''),
                'email' => $email,
                'phone' => trim($data['phone'] ?? ''),
                'website_url' => trim($data['website_url'] ?? ''),
                'vat_number' => trim($data['vat_number'] ?? ''),
                'description' => trim($data['description'] ?? ''),
                'errors' => $rowErrors,
            ];
        }

        fclose($handle);

        session()->put('crm_organization_import_preview', $rows);

        return redirect()->route('laravel-crm.organizations.import');
    }

    /**
     * Stream a sample CSV file for organizations import.
     */
    public function sampleCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="organizations-import-sample.csv"',
        ];

        $rows = [
            ['name', 'email', 'phone', 'website_url', 'vat_number', 'description'],
            ['Acme Inc', 'hello@acme.com', '+1 555 0100', 'https://acme.com', '', 'Sample customer'],
            ['Globex Corp', 'info@globex.com', '+1 555 0101', 'https://globex.com', '', ''],
            ['Initech', '', '', '', '', ''],
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
