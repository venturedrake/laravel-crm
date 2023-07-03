<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\StoreInvoiceRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateInvoiceRequest;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Services\OrganisationService;
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
     * @var OrganisationService
     */
    private $organisationService;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    public function __construct(SettingService $settingService, PersonService $personService, OrganisationService $organisationService, InvoiceService $invoiceService)
    {
        $this->settingService = $settingService;
        $this->personService = $personService;
        $this->organisationService = $organisationService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Invoice::resetSearchValue($request);
        $params = Invoice::filters($request);

        if (Invoice::filter($params)->get()->count() < 30) {
            $invoices = Invoice::filter($params)->latest()->get();
        } else {
            $invoices = Invoice::filter($params)->latest()->paginate(30);
        }

        return view('laravel-crm::invoices.index', [
            'invoices' => $invoices,
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
            case "person":
                $person = Person::find($request->id);

                break;

            case "organisation":
                $organisation = Organisation::find($request->id);

                break;

            case "order":
                $order = Order::find($request->id);
                $person = $order->person;
                $organisation = $order->organisation;

                break;
        }

        $invoiceTerms = $this->settingService->get('invoice_terms');

        return view('laravel-crm::invoices.create', [
            'person' => $person ?? null,
            'organisation' => $organisation ?? null,
            'order' => $order ?? null,
            'prefix' => $this->settingService->get('invoice_prefix'),
            'number' => (Invoice::latest()->first()->number ?? 1000) + 1,
            'invoiceTerms' => $invoiceTerms,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInvoiceRequest $request)
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

        $this->invoiceService->create($request, $person ?? null, $organisation ?? null);

        flash(ucfirst(trans('laravel-crm::lang.invoice_created')))->success()->important();

        return redirect(route('laravel-crm.invoices.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        if ($invoice->person) {
            $email = $invoice->person->getPrimaryEmail();
            $phone = $invoice->person->getPrimaryPhone();
            $address = $invoice->person->getPrimaryAddress();
        }

        if ($invoice->organisation) {
            $organisation_address = $invoice->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::invoices.show', [
            'invoice' => $invoice,
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
    public function edit(Invoice $invoice)
    {
        if ($invoice->person) {
            $email = $invoice->person->getPrimaryEmail();
            $phone = $invoice->person->getPrimaryPhone();
        }

        if ($invoice->organisation) {
            $address = $invoice->organisation->getPrimaryAddress();
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
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

        $invoice = $this->invoiceService->update($request, $invoice, $person ?? null, $organisation ?? null);

        flash(ucfirst(trans('laravel-crm::lang.invoice_updated')))->success()->important();

        return redirect(route('laravel-crm.invoices.show', $invoice));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        flash(ucfirst(trans('laravel-crm::lang.invoice_deleted')))->success()->important();

        return redirect(route('laravel-crm.invoices.index'));
    }

    public function download(Invoice $invoice)
    {
        if ($invoice->person) {
            $email = $invoice->person->getPrimaryEmail();
            $phone = $invoice->person->getPrimaryPhone();
            $address = $invoice->person->getPrimaryAddress();
        }

        if ($invoice->organisation) {
            $organisation_address = $invoice->organisation->getPrimaryAddress();
        }

        return Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::invoices.pdf', [
                'invoice' => $invoice,
                'contactDetails' => $this->settingService->get('invoice_contact_details')->value ?? null,
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organisation_address' => $organisation_address ?? null,
                'fromName' => $this->settingService->get('organisation_name')->value ?? null,
                'logo' => $this->settingService->get('logo_file')->value ?? null,
            ])->download('invoice-'.strtolower($invoice->invoice_id).'.pdf');
    }
}
