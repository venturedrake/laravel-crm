<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class SendInvoice extends Component
{
    use NotifyToast;
    
    public $invoice;

    public $to;

    public $subject;
    
    public $message;

    public $cc;

    public $signedUrl;

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
        $this->validate();
        
        $this->generateUrl();

        Mail::send(new \VentureDrake\LaravelCrm\Mail\SendQuote([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlineInvoiceLink' => $this->signedUrl,
        ]));

        /*Notification::route('mail', $this->email)
            ->notify(new OrderSharedNotification(auth()->user(), auth()->user()->currentTeam, $this->order, $this->signedUrl));*/

        $this->notify(
            'Invoice sent',
        );
        
        $this->resetFields();

        $this->dispatchBrowserEvent('invoiceSent');
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
        return view('laravel-crm::livewire.send-invoice');
    }
}
