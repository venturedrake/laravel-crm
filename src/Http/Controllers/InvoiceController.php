<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\UpdateInvoiceRequest;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Services\SettingService;

class InvoiceController extends Controller
{
    /**
     * @var SettingService
     */
    private $settingService;

    /**
     * @var PersonService
     */
    private $personService;

    /**
     * @var OrganizationService
     */
    private $organizationService;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    public function __construct(SettingService $settingService, PersonService $personService, OrganizationService $organizationService, InvoiceService $invoiceService)
    {
        $this->settingService = $settingService;
        $this->personService = $personService;
        $this->organizationService = $organizationService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        return view('laravel-crm::invoices.index');
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
                $fromModel = Organization::find($request->id);

                break;

            case 'person':
                $fromModel = Person::find($request->id);

                break;

            case 'order':
                $fromModel = Order::find($request->id);

                break;
        }

        return view('laravel-crm::invoices.create', [
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
    public function show(Invoice $invoice)
    {
        if ($invoice->person) {
            $email = $invoice->person->getPrimaryEmail();
            $phone = $invoice->person->getPrimaryPhone();
            $address = $invoice->person->getPrimaryAddress();
        }

        if ($invoice->organization) {
            $organization_address = $invoice->organization->getPrimaryAddress();
        }

        return view('laravel-crm::invoices.show', [
            'invoice' => $invoice,
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
    public function edit(Invoice $invoice)
    {
        if ($invoice->person) {
            $email = $invoice->person->getPrimaryEmail();
            $phone = $invoice->person->getPrimaryPhone();
        }

        if ($invoice->organization) {
            $address = $invoice->organization->getPrimaryAddress();
        }

        return view('laravel-crm::invoices.edit', [
            'invoice' => $invoice,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
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

        $invoice = $this->invoiceService->update($request, $invoice, $person ?? null, $organization ?? null);

        flash(ucfirst(trans('laravel-crm::lang.invoice_updated')))->success()->important();

        return redirect(route('laravel-crm.invoices.show', $invoice));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        flash(ucfirst(trans('laravel-crm::lang.invoice_deleted')))->success()->important();

        return redirect(route('laravel-crm.invoices.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Invoice::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.invoices.index'));
        }

        $params = Invoice::filters($request, 'search');

        $invoices = Invoice::filter($params)
            ->select(
                config('laravel-crm.db_table_prefix').'invoices.*',
                config('laravel-crm.db_table_prefix').'people.first_name',
                config('laravel-crm.db_table_prefix').'people.middle_name',
                config('laravel-crm.db_table_prefix').'people.last_name',
                config('laravel-crm.db_table_prefix').'people.maiden_name',
                config('laravel-crm.db_table_prefix').'organizations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'invoices.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organizations', config('laravel-crm.db_table_prefix').'invoices.organization_id', '=', config('laravel-crm.db_table_prefix').'organizations.id')
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

        return view('laravel-crm::invoices.index', [
            'invoices' => $invoices,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    public function download(Invoice $invoice)
    {
        if ($invoice->person) {
            $email = $invoice->person->getPrimaryEmail();
            $phone = $invoice->person->getPrimaryPhone();
            $address = $invoice->person->getPrimaryAddress();
        }

        if ($invoice->organization) {
            $organization_address = $invoice->organization->getPrimaryAddress();
        }

        return Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::invoices.pdf', [
                'invoice' => $invoice,
                'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                'taxName' => app('laravel-crm.settings')->get('tax_name', 'Tax'),
                'contactDetails' => app('laravel-crm.settings')->get('invoice_contact_details', null),
                'paymentInstructions' => app('laravel-crm.settings')->get('invoice_payment_instructions', null),
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organization_address' => $organization_address ?? null,
                'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                'logo' => app('laravel-crm.settings')->get('logo_file', null),
            ])->download('invoice-'.strtolower($invoice->xeroInvoice->number ?? $invoice->invoice_id).'.pdf');
    }
}
