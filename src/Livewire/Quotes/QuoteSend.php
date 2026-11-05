<?php

namespace VentureDrake\LaravelCrm\Livewire\Quotes;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\ExceptionInterface as MimeExceptionInterface;
use VentureDrake\LaravelCrm\Http\Rules\EmailAddressList;
use VentureDrake\LaravelCrm\Mail\SendQuote;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\QuoteStateService;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;
use VentureDrake\LaravelCrm\Support\PdfLogo;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\PortalLink;

class QuoteSend extends Component
{
    use AuthorizesRequests, Toast;

    public bool $showSendQuote = false;

    public $quote;

    public $to;

    public $subject;

    public $message;

    public $cc;

    public $pdf;

    public $signedUrl;

    public $type = 'button';

    #[Locked]
    public bool $shared = false;

    /**
     * Returns validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'to' => ['required', new EmailAddressList],
            'subject' => 'required|string',
            'message' => 'required|string',
        ];
    }

    /**
     * With no quote this is the one instance a board or index page shares across its
     * rows, opened for a row by the quote-send event. Pre-filling waits for open(),
     * because rendering the templates here costs every row whether it is sent or not.
     */
    public function mount($quote = null)
    {
        $this->quote = $quote;
        $this->shared = $quote === null;
    }

    #[On('quote-send')]
    public function open(?int $id = null): void
    {
        if (! $this->shared && $id !== null && $id !== $this->quote->id) {
            return;
        }

        if (! $quote = $this->shared ? Quote::find($id) : $this->quote) {
            return;
        }

        $this->authorize('view', $quote);

        $this->resetValidation();
        $this->reset('cc');

        $this->quote = $quote;
        $this->to = ($quote->person) ? ($quote->person->getPrimaryEmail()->address ?? null) : null;
        $this->subject = view('laravel-crm::mail.templates.send-quote.subject', ['quote' => $quote])->render();
        $this->message = view('laravel-crm::mail.templates.send-quote.message', ['quote' => $quote])->render();

        $this->showSendQuote = true;
    }

    public function send()
    {
        abort_unless($this->quote, 404);

        $this->authorize('update', $this->quote);

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

        File::ensureDirectoryExists(storage_path('app/'.$pdfLocation));

        $this->pdf = 'app/'.$pdfLocation.'quote-'.strtolower($this->quote->quote_id).'.pdf';

        Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView(PdfTemplateRegistry::viewForModel('quote', $this->quote), [
                'quote' => $this->quote,
                'dateFormat' => app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format')),
                'contactDetails' => PdfContactDetails::for('quote'),
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organization_address' => $organization_address ?? null,
                'fromName' => app('laravel-crm.settings')->get('organization_name', null),
                'logo' => PdfLogo::fromSettings(),
            ])->save(storage_path($this->pdf));

        // A refused connection or a rejected address surfaces here. Keep the
        // drawer open with what was typed rather than letting it become a 500.
        try {
            Mail::send(new SendQuote([
                'to' => EmailAddressList::parse($this->to),
                'subject' => $this->subject,
                'message' => $this->message,
                'cc' => $this->cc,
                'onlineQuoteLink' => $this->signedUrl,
                'pdf' => $this->pdf,
            ]));
        } catch (TransportExceptionInterface|MimeExceptionInterface $e) {
            Log::error('[laravel-crm] quote send failed', [
                'exception' => $e,
                'quote_id' => $this->quote->id,
            ]);

            $this->error(ucfirst(__('laravel-crm::lang.email_could_not_be_sent', [
                'reason' => Str::limit($e->getMessage(), 150),
            ])));

            return;
        }

        app(QuoteStateService::class)->markSent($this->quote);

        $this->dispatch('refreshQuote');

        $this->success(
            ucfirst(trans('laravel-crm::lang.quote_sent_successfully'))
        );

        $this->resetFields();

        $this->showSendQuote = false;
    }

    public function generateUrl()
    {
        $this->signedUrl = PortalLink::for($this->quote);
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
