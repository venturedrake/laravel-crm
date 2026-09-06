<?php

namespace VentureDrake\LaravelCrm\Livewire\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Mail\SendInvoice;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;
use VentureDrake\LaravelCrm\Support\PdfLogo;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\PortalLink;

class InvoiceSend extends Component
{
    use AuthorizesRequests, Toast;

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
        $this->authorize('update', $this->invoice);

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

        File::ensureDirectoryExists(storage_path('app/'.$pdfLocation));

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

        Mail::send(new SendInvoice([
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
        $this->signedUrl = PortalLink::for($this->invoice);
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
