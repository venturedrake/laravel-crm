<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use VentureDrake\LaravelCrm\Traits\NotifyToast;

class SendQuote extends Component
{
    use NotifyToast;
    
    public $quote;

    public $to;

    public $subject;
    
    public $message;

    public $cc;

    public $signedUrl;

    public function mount($quote)
    {
        $this->quote = $quote;
        $this->to = ($quote->person) ? ($quote->person->getPrimaryEmail()->address ?? null) : null;
        $this->subject = view('laravel-crm::mail.templates.send-quote.subject', ['quote' => $this->quote])->render();
        $this->message = view('laravel-crm::mail.templates.send-quote.message', ['quote' => $this->quote])->render();
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

        if ($this->quote->person) {
            $email = $this->quote->person->getPrimaryEmail();
            $phone = $this->quote->person->getPrimaryPhone();
            $address = $this->quote->person->getPrimaryAddress();
        }

        if ($this->quote->organisation) {
            $organisation_address = $this->quote->organisation->getPrimaryAddress();
        }
        
        // Create PDF for attaching

        Mail::send(new \VentureDrake\LaravelCrm\Mail\SendQuote([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlineQuoteLink' => $this->signedUrl,
        ]));

        /*Notification::route('mail', $this->email)
            ->notify(new OrderSharedNotification(auth()->user(), auth()->user()->currentTeam, $this->order, $this->signedUrl));*/

        $this->notify(
            'Quote sent',
        );
        
        $this->resetFields();

        $this->dispatchBrowserEvent('quoteSent');
    }

    public function generateUrl()
    {
        $this->signedUrl = URL::temporarySignedRoute(
            'laravel-crm.portal.quotes.show',
            now()->addDays(14),
            [
                'quote' => $this->quote,
            ]
        );
    }

    private function resetFields()
    {
        $this->reset('to', 'subject', 'message', 'cc');
    }

    public function render()
    {
        return view('laravel-crm::livewire.send-quote');
    }
}
