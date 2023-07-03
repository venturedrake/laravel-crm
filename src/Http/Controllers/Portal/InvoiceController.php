<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Services\SettingService;

class InvoiceController extends Controller
{
    /**
     * @var SettingService
     */
    private $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Invoice $invoice)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        if ($invoice->person) {
            $email = $invoice->person->getPrimaryEmail();
            $phone = $invoice->person->getPrimaryPhone();
            $address = $invoice->person->getPrimaryAddress();
        }

        if ($invoice->organisation) {
            $organisation_address = $invoice->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::portal.invoices.show', [
            'invoice' => $invoice,
            'contactDetails' => $this->settingService->get('invoice_contact_details')->value ?? null,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null,
            'fromName' => $this->settingService->get('organisation_name')->value ?? null,
            'logo' => $this->settingService->get('logo_file')->value ?? null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function process(Invoice $invoice, Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        switch ($request->action) {
            case "download":
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

                break;
        }


        return back();
    }
}
