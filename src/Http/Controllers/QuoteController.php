<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Services\QuoteService;

class QuoteController extends Controller
{
    /**
     * @var QuoteService
     */
    private $quoteService;

    /**
     * @var PersonService
     */
    private $personService;

    /**
     * @var OrganizationService
     */
    private $organizationService;

    /**
     * @var OrderService
     */
    private $orderService;

    public function __construct(QuoteService $quoteService, PersonService $personService, OrganizationService $organizationService, OrderService $orderService)
    {
        $this->quoteService = $quoteService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $viewSetting = auth()->user()->crmSettings()->where('name', 'view_quotes')->first();

        if (! $viewSetting) {
            auth()->user()->crmSettings()->create([
                'name' => 'view_quotes',
                'value' => 'list',
            ]);
        } elseif ($viewSetting->value == 'board') {
            return redirect(route('laravel-crm.quotes.board'));
        }

        return view('laravel-crm::quotes.index');
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

            case 'deal':
                $fromModel = Deal::find($request->id);
                break;

            case 'organization':
                $fromModel = Organization::find($request->id);

                break;

            case 'person':
                $fromModel = Person::find($request->id);

                break;
        }

        return view('laravel-crm::quotes.create', [
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
    public function show(Quote $quote)
    {
        if ($quote->person) {
            $email = $quote->person->getPrimaryEmail();
            $phone = $quote->person->getPrimaryPhone();
            $address = $quote->person->getPrimaryAddress();
        }

        if ($quote->organization) {
            $organization_address = $quote->organization->getPrimaryAddress();
        }

        return view('laravel-crm::quotes.show', [
            'quote' => $quote,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organization_address' => $organization_address ?? null,
            'orders' => $quote->orders()->latest()->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(Quote $quote)
    {
        if ($quote->person) {
            $email = $quote->person->getPrimaryEmail();
            $phone = $quote->person->getPrimaryPhone();
        }

        if ($quote->organization) {
            $address = $quote->organization->getPrimaryAddress();
        }

        return view('laravel-crm::quotes.edit', [
            'quote' => $quote,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'pipeline' => Pipeline::where('model', get_class(new Quote))->first(),
        ]);
    }

    public function search(Request $request)
    {
        $viewSetting = auth()->user()->crmSettings()->where('name', 'view_quotes')->first();

        $searchValue = Quote::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.quotes.index'));
        }

        $params = Quote::filters($request, 'search');

        $quotes = Quote::filter($params)
            ->select(
                config('laravel-crm.db_table_prefix').'quotes.*',
                config('laravel-crm.db_table_prefix').'people.first_name',
                config('laravel-crm.db_table_prefix').'people.middle_name',
                config('laravel-crm.db_table_prefix').'people.last_name',
                config('laravel-crm.db_table_prefix').'people.maiden_name',
                config('laravel-crm.db_table_prefix').'organizations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'quotes.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'quotes.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
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
            return view('laravel-crm::quotes.board', [
                'quotes' => $quotes,
                'searchValue' => $searchValue ?? null,
                'viewSetting' => $viewSetting->value ?? null,
            ]);
        } else {
            return view('laravel-crm::quotes.index', [
                'quotes' => $quotes,
                'searchValue' => $searchValue ?? null,
                'viewSetting' => $viewSetting->value ?? null,
                'pipeline' => Pipeline::where('model', get_class(new Quote))->first(),
            ]);
        }
    }

    public function download(Quote $quote)
    {
        if ($quote->person) {
            $email = $quote->person->getPrimaryEmail();
            $phone = $quote->person->getPrimaryPhone();
            $address = $quote->person->getPrimaryAddress();
        }

        if ($quote->organization) {
            $organization_address = $quote->organization->getPrimaryAddress();
        }

        return Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::quotes.pdf', [
                'quote' => $quote,
                'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organization_address' => $organization_address ?? null,
                'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                'logo' => app('laravel-crm.settings')->get('logo_file', null),
            ])->download('quote-'.strtolower($quote->quote_id).'.pdf');
    }

    public function list(Request $request)
    {
        auth()->user()->crmSettings()->updateOrCreate([
            'name' => 'view_quotes',
        ], [
            'value' => 'list',
        ]);

        return redirect(route('laravel-crm.quotes.index'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function board(Request $request)
    {
        auth()->user()->crmSettings()->updateOrCreate([
            'name' => 'view_quotes',
        ], [
            'value' => 'board',
        ]);

        return view('laravel-crm::quotes.board');
    }
}
