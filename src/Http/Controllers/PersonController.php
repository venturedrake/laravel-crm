<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VentureDrake\LaravelCrm\Http\Requests\StorePersonRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePersonRequest;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Organization;
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
     * @return Response
     */
    public function index(Request $request)
    {
        /*Person::resetSearchValue($request);
        $params = Person::filters($request);
        $people = Person::filter($params);

        // This is  not the best, will refactor. Problem with trying to sort encryoted fields
        if (request()->only(['sort', 'direction']) && config('laravel-crm.encrypt_db_fields')) {
            $people = $people->get();

            foreach ($people as $key => $person) {
                $people[$key]->first_name_decrypted = $person->first_name;
                $people[$key]->last_name_decrypted = $person->last_name;
                $people[$key]->organization_name_decrypted = $person->organization->name ?? null;
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
        }*/

        return view('laravel-crm::people.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Request $request)
    {
        switch ($request->model) {
            case 'organization':
                $organization = Organization::find($request->id);

                break;
        }

        return view('laravel-crm::people.create', [
            'organization' => $organization ?? null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StorePersonRequest $request)
    {
        $person = $this->personService->create($request);

        $person->labels()->sync($request->labels ?? []);

        if ($request->organization_name) {
            if (! $request->organization_id) {
                $organization = Organization::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'name' => $request->organization_name,
                    'user_owner_id' => $request->user_owner_id,
                ]);
                $person->organization()->associate($organization);
            } else {
                $person->organization()->associate(Organization::find($request->organization_id));
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
     * @return Response
     */
    public function show(Person $person)
    {
        $organization = $person->organization;
        if ($organization) {
            $organizationAddress = $organization->getPrimaryAddress();
        }

        return view('laravel-crm::people.show', [
            'person' => $person,
            'emails' => $person->emails,
            'phones' => $person->phones,
            'addresses' => $person->addresses,
            'organization' => $organization ?? null,
            'organization_address' => $organizationAddress ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
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
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdatePersonRequest $request, Person $person)
    {
        $person = $this->personService->update($person, $request);

        $person->labels()->sync($request->labels ?? []);

        if ($request->organization_name) {
            if (! $request->organization_id) {
                $organization = Organization::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'name' => $request->organization_name,
                    'user_owner_id' => $request->user_owner_id,
                ]);
                $person->organization()->associate($organization);
            } else {
                $person->organization()->associate(Organization::find($request->organization_id));
            }
            $person->save();
        } elseif (trim($request->organization_name) == '' && $person->organization) {
            $person->organization()->dissociate();
            $person->save();
        }

        flash(ucfirst(trans('laravel-crm::lang.person_updated')))->success()->important();

        return redirect(route('laravel-crm.people.show', $person));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Person $person)
    {
        foreach (Contact::where([
            'entityable_type' => $person->getMorphClass(),
            'entityable_id' => $person->id,
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

    /**
     * Show the bulk import form.
     */
    public function import()
    {
        return view('laravel-crm::people.import');
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

        $required = ['first_name'];
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

            if (empty(trim($data['first_name'] ?? ''))) {
                $rowErrors[] = __('laravel-crm::lang.import_first_name_required');
            }

            $email = trim($data['email'] ?? '');
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = __('laravel-crm::lang.import_email_invalid');
            }

            $rows[] = [
                'row' => $rowNumber,
                'first_name' => trim($data['first_name'] ?? ''),
                'last_name' => trim($data['last_name'] ?? ''),
                'title' => trim($data['title'] ?? ''),
                'email' => $email,
                'phone' => trim($data['phone'] ?? ''),
                'organization_name' => trim($data['organization_name'] ?? ''),
                'description' => trim($data['description'] ?? ''),
                'errors' => $rowErrors,
            ];
        }

        fclose($handle);

        session()->put('crm_person_import_preview', $rows);

        return redirect()->route('laravel-crm.people.import');
    }

    /**
     * Stream a sample CSV file for people import.
     */
    public function sampleCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="people-import-sample.csv"',
        ];

        $rows = [
            ['first_name', 'last_name', 'title', 'email', 'phone', 'organization_name', 'description'],
            ['Jane', 'Smith', 'Mr', 'jane@example.com', '+1 555 0100', 'Acme Inc', 'Key contact'],
            ['John', 'Doe', '', 'john@example.com', '+1 555 0101', 'Acme Inc', ''],
            ['Alice', 'Brown', '', '', '+1 555 0102', '', ''],
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
