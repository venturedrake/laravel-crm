<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;
use VentureDrake\LaravelCrm\Support\PdfLogo;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\PortalDocument;

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
     * @return Response
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
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

        if ($invoice->organization) {
            $organization_address = $invoice->organization->getPrimaryAddress();
        }

        return view('laravel-crm::portal.invoices.show', [
            'invoice' => $invoice,
            // The page renders the record's chosen template, so the web view
            // and the Download button produce the same document. Kept as an
            // inline literal array — PdfViewDataContractTest statically reads
            // these keys and diffs them against the blades, and hiding them
            // behind a shared helper would drop that guardrail.
            'documentHtml' => PortalDocument::html(PdfTemplateRegistry::viewForModel('invoice', $invoice), [
                'invoice' => $invoice,
                'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                'taxName' => app('laravel-crm.settings')->get('tax_name', 'Tax'),
                'contactDetails' => PdfContactDetails::for('invoice'),
                'paymentInstructions' => app('laravel-crm.settings')->get('invoice_payment_instructions', null),
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organization_address' => $organization_address ?? null,
                'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                // The raw `logo_file` setting rather than PdfLogo::fromSettings():
                // the templates fall back to asset('storage/'.$logo) for a
                // non-data URI, which is what a browser wants. The base64 form
                // exists for DomPDF, which cannot fetch it.
                'logo' => app('laravel-crm.settings')->get('logo_file', null),
            ]),
            'contactDetails' => PdfContactDetails::for('invoice'),
            'paymentInstructions' => app('laravel-crm.settings')->get('invoice_payment_instructions', null),
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organization_address' => $organization_address ?? null,
            'fromName' => app('laravel-crm.settings')->get('organization_name', null),
            'logo' => app('laravel-crm.settings')->get('logo_file', null),
            'timezone' => $this->settingService->get('timezone', config('laravel-crm.timezone')),
            'dateFormat' => $this->settingService->get('date_format', config('laravel-crm.date_format')),
            'taxName' => $this->settingService->get('tax_name', 'Tax'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function process(Invoice $invoice, Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        switch ($request->action) {
            case 'download':
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
                    ->loadView(PdfTemplateRegistry::viewForModel('invoice', $invoice), [
                        'invoice' => $invoice,
                        'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                        'taxName' => app('laravel-crm.settings')->get('tax_name', 'Tax'),
                        'contactDetails' => PdfContactDetails::for('invoice'),
                        'paymentInstructions' => app('laravel-crm.settings')->get('invoice_payment_instructions', null),
                        'email' => $email ?? null,
                        'phone' => $phone ?? null,
                        'address' => $address ?? null,
                        'organization_address' => $organization_address ?? null,
                        'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                        'logo' => PdfLogo::fromSettings(),
                    ])->download('invoice-'.strtolower($invoice->invoice_id).'.pdf');

                break;
        }

        return back();
    }
}
