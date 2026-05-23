<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Services\SettingService;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private SettingService $settingService,
    ) {}

    /**
     * Display the purchase order to a recipient via a signed URL.
     */
    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        if ($purchaseOrder->person) {
            $email = $purchaseOrder->person->getPrimaryEmail();
            $phone = $purchaseOrder->person->getPrimaryPhone();
            $address = $purchaseOrder->person->getPrimaryAddress();
        }

        if ($purchaseOrder->organization) {
            $organization_address = $purchaseOrder->organization->getPrimaryAddress();
        }

        return response()->view('laravel-crm::portal.purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
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
     * Process recipient-side actions (e.g. download PDF) via a signed URL.
     */
    public function process(PurchaseOrder $purchaseOrder, Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        if ($request->action === 'download') {
            if ($purchaseOrder->person) {
                $email = $purchaseOrder->person->getPrimaryEmail();
                $phone = $purchaseOrder->person->getPrimaryPhone();
                $address = $purchaseOrder->person->getPrimaryAddress();
            }

            if ($purchaseOrder->organization) {
                $organization_address = $purchaseOrder->organization->getPrimaryAddress();
            }

            return Pdf::setOption([
                'fontDir' => public_path('vendor/laravel-crm/fonts'),
            ])
                ->loadView('laravel-crm::purchase-orders.pdf', [
                    'purchaseOrder' => $purchaseOrder,
                    'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                    'taxName' => app('laravel-crm.settings')->get('tax_name', 'Tax'),
                    'contactDetails' => app('laravel-crm.settings')->get('purchase_order_contact_details', null),
                    'email' => $email ?? null,
                    'phone' => $phone ?? null,
                    'address' => $address ?? null,
                    'organization_address' => $organization_address ?? null,
                    'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                    'logo' => app('laravel-crm.settings')->get('logo_file', null),
                ])->download('purchase-order-'.strtolower($purchaseOrder->purchase_order_id).'.pdf');
        }

        return back();
    }
}

