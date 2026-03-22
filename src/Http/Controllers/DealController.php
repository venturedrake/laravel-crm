<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
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

        return view('laravel-crm::deals.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Request $request)
    {
        switch ($request->model) {
            case 'lead':
                $fromModel = Lead::find($request->id);
                break;

            case 'client':
                $fromModel = Customer::find($request->id);

                break;

            case 'organization':
                $fromModel = Organization::find($request->id);

                break;

            case 'person':
                $fromModel = Person::find($request->id);

                break;
        }

        return view('laravel-crm::deals.create', [
            'fromModelType' => $request->model,
            'fromModelId' => $request->id,
            'stage' => $request->stage ?? null,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
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
     * @return Response
     */
    public function edit(Deal $deal)
    {
        return view('laravel-crm::deals.edit', compact('deal'));
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
     * @return Response
     */
    public function board(Request $request)
    {
        auth()->user()->crmSettings()->updateOrCreate([
            'name' => 'view_deals',
        ], [
            'value' => 'board',
        ]);

        return view('laravel-crm::deals.board');
    }
}
