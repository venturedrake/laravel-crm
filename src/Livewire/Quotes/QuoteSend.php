<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Mail\SendQuote;

class QuoteSend extends Component
{
    use Toast;

    public bool $showSendQuote = false;

    public $quote;

    public $to;

    public $subject;

    public $message;

    public $cc;

    public $pdf;

    public $signedUrl;

    public $type = 'button';

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

    public function mount($quote)
    {
        $this->quote = $quote;
        $this->to = ($quote->person) ? ($quote->person->getPrimaryEmail()->address ?? null) : null;
        $this->subject = view('laravel-crm::mail.templates.send-quote.subject', ['quote' => $this->quote])->render();
        $this->message = view('laravel-crm::mail.templates.send-quote.message', ['quote' => $this->quote])->render();
    }

    #[On('quote-send')]
    public function toggle($id)
    {
        if ($this->quote->id === $id) {
            $this->showSendQuote = ! $this->showSendQuote;
        }
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

        if ($this->quote->organization) {
            $organization_address = $this->quote->organization->getPrimaryAddress();
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
                'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organization_address' => $organization_address ?? null,
                'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                'logo' => app('laravel-crm.settings')->get('logo_file', null),
            ])->save(storage_path($this->pdf));

        Mail::send(new SendQuote([
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $this->message,
            'cc' => $this->cc,
            'onlineQuoteLink' => $this->signedUrl,
            'pdf' => $this->pdf,
        ]));

        $this->success(
            ucfirst(trans('laravel-crm::lang.quote_sent_successfully'))
        );

        $this->resetFields();

        $this->showSendQuote = false;
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
        return view('laravel-crm::livewire.quotes.quote-send');
    }
}
