<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class SendPurchaseOrder extends Component
{
    use NotifyToast;

    private $settingService;

    public $purchaseOrder;

    public $to;

    public $subject;

    public $message;

    public $cc;

    public $pdf;

    public $signedUrl;

    public function boot(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function mount($purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->to = ($purchaseOrder->person) ? ($purchaseOrder->person->getPrimaryEmail()->address ?? null) : null;
        $this->subject = view('laravel-crm::mail.templates.send-purchase-order.subject', ['purchaseOrder' => $this->purchaseOrder])->render();
        $this->message = view('laravel-crm::mail.templates.send-purchase-order.message', ['purchaseOrder' => $this->purchaseOrder])->render();
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'to' => 'required|string',
            'subject' => 'required|string',
            'message' => 'required|string',
        ];
    }

    public function send()
    {
        $this->validate();

        // $this->generateUrl();

        $pdfLocation = 'laravel-crm/'.strtolower(class_basename($this->purchaseOrder)).'/'.$this->purchaseOrder->id.'/';

        if (! File::exists($pdfLocation)) {
            Storage::makeDirectory($pdfLocation);
        }

        $this->pdf = 'app/'.$pdfLocation.'purchase-order-'.strtolower($this->purchaseOrder->xeroPurchaseOrder->number ?? $this->purchaseOrder->_id).'.pdf';

        Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::purchase-orders.pdf', [
                'purchaseOrder' => $this->purchaseOrder,
                'contactDetails' => $this->settingService->get('purchase_order_contact_details')->value ?? null,
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organisation_address' => $organisation_address ?? null,
                'fromName' => $this->settingService->get('organisation_name')->value ?? null,
                'logo' => $this->settingService->get('logo_file')->value ?? null,
            ])->save(storage_path($this->pdf));
        ;

        Mail::send(new \VentureDrake\LaravelCrm\Mail\SendPurchaseOrder([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlinePurchaseOrderLink' => $this->signedUrl,
            'pdf' => $this->pdf,
        ]));

        $this->notify(
            'Purchase order sent',
        );

        $this->purchaseOrder->update([
            'sent' => 1
        ]);

        $this->resetFields();

        $this->dispatchBrowserEvent('purchaseOrderSent');
    }

    public function generateUrl()
    {
        $this->signedUrl = URL::temporarySignedRoute(
            'laravel-crm.portal.purchase-orders.show',
            now()->addDays(14),
            [
                'purchaseOrder' => $this->purchaseOrder,
            ]
        );
    }

    private function resetFields()
    {
        $this->reset('to', 'subject', 'message', 'cc');
    }

    public function render()
    {
        return view('laravel-crm::livewire.send-purchase-order');
    }
}
