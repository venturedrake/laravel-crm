<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Invoice;

class InvoiceSend extends Component
{
    use Toast;

    public bool $showSendInvoice = false;

    public $invoice;

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

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->to = ($invoice->person) ? ($invoice->person->getPrimaryEmail()->address ?? null) : null;
        $this->subject = view('laravel-crm::mail.templates.send-invoice.subject', ['invoice' => $this->invoice])->render();
        $this->message = view('laravel-crm::mail.templates.send-invoice.message', ['invoice' => $this->invoice])->render();
    }

    public function send()
    {
        $this->validate();

        $this->generateUrl();

        if ($this->invoice->person) {
            $email = $this->invoice->person->getPrimaryEmail();
            $phone = $this->invoice->person->getPrimaryPhone();
            $address = $this->invoice->person->getPrimaryAddress();
        }

        if ($this->invoice->organization) {
            $organization_address = $this->invoice->organization->getPrimaryAddress();
        }

        $pdfLocation = 'laravel-crm/'.strtolower(class_basename($this->invoice)).'/'.$this->invoice->id.'/';

        if (! File::exists($pdfLocation)) {
            Storage::makeDirectory($pdfLocation);
        }

        $this->pdf = 'app/'.$pdfLocation.'invoice-'.strtolower($this->invoice->invoice_id).'.pdf';

        Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::invoices.pdf', [
                'invoice' => $this->invoice,
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organization_address' => $organization_address ?? null,
                'fromName' => $this->settingService->get('organization_name')->value ?? null,
                'logo' => $this->settingService->get('logo_file')->value ?? null,
            ])->save(storage_path($this->pdf));

        Mail::send(new \VentureDrake\LaravelCrm\Mail\SendInvoice([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlineInvoiceLink' => $this->signedUrl,
            'pdf' => $this->pdf,
        ]));

        $this->success(
            ucfirst(trans('laravel-crm::lang.invoice_sent'))
        );

        $this->resetFields();

        $this->showSendInvoice = false;
    }

    public function generateUrl()
    {
        $this->signedUrl = URL::temporarySignedRoute(
            'laravel-crm.portal.invoices.show',
            now()->addDays(14),
            [
                'invoice' => $this->invoice,
            ]
        );
    }

    private function resetFields()
    {
        $this->reset('to', 'subject', 'message', 'cc');
    }

    public function render()
    {
        return view('laravel-crm::livewire.invoices.invoice-send');
    }
}
