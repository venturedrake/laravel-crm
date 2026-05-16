<?php

namespace VentureDrake\LaravelCrm\Livewire\PurchaseOrders;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Mail\SendPurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;

class PurchaseOrderSend extends Component
{
    use Toast;

    public bool $showSendPurchaseOrder = false;

    public $purchaseOrder;

    public $to;

    public $subject;

    public $message;

    public $cc;

    public $pdf;

    public $signedUrl;

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

    public function mount(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->to = ($purchaseOrder->person) ? ($purchaseOrder->person->getPrimaryEmail()->address ?? null) : null;
        $this->subject = view('laravel-crm::mail.templates.send-purchase-order.subject', ['purchaseOrder' => $this->purchaseOrder])->render();
        $this->message = view('laravel-crm::mail.templates.send-purchase-order.message', ['purchaseOrder' => $this->purchaseOrder])->render();
    }

    public function send()
    {
        $this->validate();

        $this->generateUrl();

        if ($this->purchaseOrder->person) {
            $email = $this->purchaseOrder->person->getPrimaryEmail();
            $phone = $this->purchaseOrder->person->getPrimaryPhone();
            $address = $this->purchaseOrder->person->getPrimaryAddress();
        }

        if ($this->purchaseOrder->organization) {
            $organization_address = $this->purchaseOrder->organization->getPrimaryAddress();
        }

        $pdfLocation = 'laravel-crm/'.strtolower(class_basename($this->purchaseOrder)).'/'.$this->purchaseOrder->id.'/';

        if (! File::exists($pdfLocation)) {
            Storage::makeDirectory($pdfLocation);
        }

        $this->pdf = 'app/'.$pdfLocation.'purchase-order-'.strtolower($this->purchaseOrder->purchase_order_id).'.pdf';

        Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::purchase-orders.pdf', [
                'purchaseOrder' => $this->purchaseOrder,
                'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organization_address' => $organization_address ?? null,
                'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                'logo' => app('laravel-crm.settings')->get('logo_file', null),
            ])->save(storage_path($this->pdf));

        Mail::send(new SendPurchaseOrder([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlinePurchaseOrderLink' => $this->signedUrl,
            'pdf' => $this->pdf,
        ]));

        $this->success(
            ucfirst(trans('laravel-crm::lang.purchase_order_sent'))
        );

        $this->resetFields();

        $this->showSendPurchaseOrder = false;
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
        return view('laravel-crm::livewire.purchase-orders.purchase-order-send');
    }
}
