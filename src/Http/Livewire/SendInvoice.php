<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;
use VentureDrake\LaravelCrm\Support\PdfLogo;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\PortalLink;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class SendInvoice extends Component
{
    use AuthorizesRequests;
    use NotifyToast;

    private $settingService;

    public $invoice;

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

    public function mount($invoice)
    {
        $this->invoice = $invoice;
        $this->to = ($invoice->person) ? ($invoice->person->getPrimaryEmail()->address ?? null) : null;
        $this->subject = view('laravel-crm::mail.templates.send-invoice.subject', ['invoice' => $this->invoice])->render();
        $this->message = view('laravel-crm::mail.templates.send-invoice.message', ['invoice' => $this->invoice])->render();
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
        $this->authorize('update', $this->invoice);

        $this->validate();

        $this->generateUrl();

        $pdfLocation = 'laravel-crm/'.strtolower(class_basename($this->invoice)).'/'.$this->invoice->id.'/';

        if (! File::exists($pdfLocation)) {
            Storage::makeDirectory($pdfLocation);
        }

        $this->pdf = 'app/'.$pdfLocation.'invoice-'.strtolower($this->invoice->invoice_id).'.pdf';

        Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView(PdfTemplateRegistry::viewForModel('invoice', $this->invoice), [
                'invoice' => $this->invoice,
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
            ])->save(storage_path($this->pdf));

        Mail::send(new \VentureDrake\LaravelCrm\Mail\SendInvoice([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlineInvoiceLink' => $this->signedUrl,
            'pdf' => $this->pdf,
        ]));

        $this->notify(
            'Invoice sent',
        );

        $this->invoice->update([
            'sent' => 1,
        ]);

        $this->resetFields();

        $this->dispatchBrowserEvent('invoiceSent');
    }

    public function generateUrl()
    {
        $this->signedUrl = PortalLink::for($this->invoice);
    }

    private function resetFields()
    {
        $this->reset('to', 'subject', 'message', 'cc');
    }

    public function render()
    {
        return view('laravel-crm::livewire.send-invoice');
    }
}
