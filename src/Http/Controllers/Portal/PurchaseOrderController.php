<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Portal;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;
use VentureDrake\LaravelCrm\Support\PdfLogo;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\PortalDocument;
use VentureDrake\LaravelCrm\Support\PortalTeam;

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

        // Before any settings read. The portal is anonymous, so without this
        // the team scope stands down and the From block, ABN and logo come
        // from whichever tenant the database happened to list last. Pinning
        // the shared scoped service here corrects every reader below it,
        // PdfContactDetails and PdfLogo included.
        $this->settingService->forTeam(PortalTeam::forDocument($purchaseOrder));

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
            // The page renders the record's chosen template, so the web view
            // and the Download button produce the same document. Kept as an
            // inline literal array — PdfViewDataContractTest statically reads
            // these keys and diffs them against the blades, and hiding them
            // behind a shared helper would drop that guardrail.
            'documentHtml' => PortalDocument::html(PdfTemplateRegistry::viewForModel('purchase-order', $purchaseOrder), [
                'purchaseOrder' => $purchaseOrder,
                'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                'taxName' => app('laravel-crm.settings')->get('tax_name', 'Tax'),
                'contactDetails' => PdfContactDetails::for('purchase-order'),
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

        // Same reason as show(): the downloaded PDF has to carry the same
        // branding the page did.
        $this->settingService->forTeam(PortalTeam::forDocument($purchaseOrder));

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
                ->loadView(PdfTemplateRegistry::viewForModel('purchase-order', $purchaseOrder), [
                    'purchaseOrder' => $purchaseOrder,
                    'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                    'taxName' => app('laravel-crm.settings')->get('tax_name', 'Tax'),
                    'contactDetails' => PdfContactDetails::for('purchase-order'),
                    'email' => $email ?? null,
                    'phone' => $phone ?? null,
                    'address' => $address ?? null,
                    'organization_address' => $organization_address ?? null,
                    'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                    'logo' => PdfLogo::fromSettings(),
                ])->download('purchase-order-'.strtolower($purchaseOrder->purchase_order_id).'.pdf');
        }

        return back();
    }
}
