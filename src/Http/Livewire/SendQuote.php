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

class SendQuote extends Component
{
    use NotifyToast;

    private $settingService;

    public $quote;

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

        $pdfLocation = 'laravel-crm/'.strtolower(class_basename($this->quote)).'/'.$this->quote->id.'/';

        if (! File::exists($pdfLocation)) {
            Storage::makeDirectory($pdfLocation);
        }

        $this->pdf = 'app/'.$pdfLocation.'quote-'.strtolower($this->quote->quote_id).'.pdf';

        Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::quotes.pdf', [
                'quote' => $this->quote,
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organisation_address' => $organisation_address ?? null,
                'fromName' => $this->settingService->get('organisation_name')->value ?? null,
                'logo' => $this->settingService->get('logo_file')->value ?? null,
            ])->save(storage_path($this->pdf));

        Mail::send(new \VentureDrake\LaravelCrm\Mail\SendQuote([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlineQuoteLink' => $this->signedUrl,
            'pdf' => $this->pdf,
        ]));

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
