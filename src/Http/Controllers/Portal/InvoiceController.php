<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

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

        return view('laravel-crm::portal.invoices.show', [
            'invoice' => $invoice,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null,
            'fromName' => $this->settingService->get('organisation_name')->value ?? null,
            'logo' => $this->settingService->get('logo_file')->value ?? null,
        ]);
    }
}
