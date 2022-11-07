<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class SendQuote extends Component
{
    public $quote;

    public $to;

    public $subject;
    
    public $message;

    public $cc;

    public $signedUrl;

    public function mount($quote)
    {
        $this->quote = $quote;
    }

    public function share()
    {
        /*$this->generateUrl();

        Notification::route('mail', $this->email)
            ->notify(new OrderSharedNotification(auth()->user(), auth()->user()->currentTeam, $this->order, $this->signedUrl));

        $this->resetFields();

        $this->dispatchBrowserEvent('orderShared');*/
    }

    public function generateUrl()
    {
        /*$this->signedUrl = URL::temporarySignedRoute(
            'public.orders.show', now()->addDays(14), [
                'order' => $this->order,
            ]
        );*/
    }

    private function resetFields()
    {
        $this->reset('email');
    }

    public function render()
    {
        return view('laravel-crm::livewire.send-quote');
    }
}
