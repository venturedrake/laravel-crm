<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            'contactDetails' => app('laravel-crm.settings')->get('invoice_contact_details', null),
            'paymentInstructions' => app('laravel-crm.settings')->get('invoice_payment_instructions', null),
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organization_address' => $organization_address ?? null,
            'fromName' => app('laravel-crm.settings')->get('organization_name', null),
            'logo' => app('laravel-crm.settings')->get('logo_file', null),
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

                if ($invoice->organisation) {
                    $organisation_address = $invoice->organisation->getPrimaryAddress();
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
                    ])->download('invoice-'.strtolower($invoice->invoice_id).'.pdf');

                break;
        }

        return back();
    }
}
