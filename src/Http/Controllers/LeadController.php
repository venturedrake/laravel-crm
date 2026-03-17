<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $viewSetting = auth()->user()->crmSettings()->where('name', 'view_leads')->first();

        if (! $viewSetting) {
            auth()->user()->crmSettings()->create([
                'name' => 'view_leads',
                'value' => 'list',
            ]);
        } elseif ($viewSetting->value == 'board') {
            return redirect(route('laravel-crm.leads.board'));
        }

        return view('laravel-crm::leads.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
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

        return view('laravel-crm::leads.create', [
            'client' => $client ?? null,
            'organization' => $organization ?? null,
            'person' => $person ?? null,
            'pipeline' => Pipeline::where('model', get_class(new Lead))->first(),
            'stage' => $request->stage ?? null,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Lead  $lead
     * @return Response
     */
    public function show(Lead $lead)
    {
        return view('laravel-crm::leads.show', compact('lead'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Lead  $lead
     * @return Response
     */
    public function edit(Lead $lead)
    {
        return view('laravel-crm::leads.edit', compact('lead'));
    }

    public function search(Request $request)
    {
        $viewSetting = auth()->user()->crmSettings()->where('name', 'view_leads')->first();

        $searchValue = Lead::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.leads.index'));
        }

        $params = Lead::filters($request, 'search');

        $leads = Lead::filter($params)
            ->select(
                config('laravel-crm.db_table_prefix').'leads.*',
                config('laravel-crm.db_table_prefix').'people.first_name',
                config('laravel-crm.db_table_prefix').'people.middle_name',
                config('laravel-crm.db_table_prefix').'people.last_name',
                config('laravel-crm.db_table_prefix').'people.maiden_name',
                config('laravel-crm.db_table_prefix').'organizations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'leads.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'leads.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
            ->latest()
            ->get()
            ->filter(function ($record) use ($searchValue) {
                foreach ($record->getSearchable() as $field) {
                    if (Str::contains($field, '.')) {
                        $field = explode('.', $field);

                        if (config('laravel-crm.encrypt_db_fields')) {
                            try {
                                $relatedField = decrypt($record->{$field[1]});
                            } catch (DecryptException $e) {
                            }

                            $relatedField = $record->{$field[1]};
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
            return view('laravel-crm::leads.board', [
                'leads' => $leads,
                'searchValue' => $searchValue ?? null,
                'viewSetting' => $viewSetting->value ?? null,
            ]);
        } else {
            return view('laravel-crm::leads.index', [
                'leads' => $leads,
                'searchValue' => $searchValue ?? null,
                'viewSetting' => $viewSetting->value ?? null,
                'pipeline' => Pipeline::where('model', get_class(new Lead))->first(),
            ]);
        }
    }

    public function list(Request $request)
    {
        auth()->user()->crmSettings()->updateOrCreate([
            'name' => 'view_leads',
        ], [
            'value' => 'list',
        ]);

        return redirect(route('laravel-crm.leads.index'));
    }

    /**
     * Display a leads board
     *
     * @return Response
     */
    public function board(Request $request)
    {
        auth()->user()->crmSettings()->updateOrCreate([
            'name' => 'view_leads',
        ], [
            'value' => 'board',
        ]);

        return view('laravel-crm::leads.board');
    }
}
