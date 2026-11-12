<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailMessages;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasGuestSearch;
use VentureDrake\LaravelCrm\Models\EmailMessage;
use VentureDrake\LaravelCrm\Services\EmailMessageService;
use VentureDrake\LaravelCrm\Support\EmailHtmlSanitizer;
use VentureDrake\LaravelCrm\Support\PastedEmailParser;

/**
 * The Emails tab on a record: the form that logs an email, and the list of
 * emails logged against the record.
 */
class EmailMessageRelated extends Component
{
    use AuthorizesRequests, HasGuestSearch, Toast;

    public $model = null;

    public $emailMessages = [];

    public string $direction = EmailMessage::OUTBOUND;

    public $sent_at;

    public $from_address;

    public $from_name;

    public $to;

    public $cc;

    public $subject;

    public $body_html;

    public $attachment_names;

    /**
     * Other people involved. Named `guests` because the picker is the one
     * calls, meetings and lunches use (HasGuestSearch).
     */
    public array $guests = [];

    public string $source = 'manual';

    public array $data = [];

    public function mount(): void
    {
        $this->resetFields();
    }

    /**
     * Swap the suggested addresses when the direction changes, as long as
     * the user has not typed their own.
     */
    public function updatedDirection(): void
    {
        [$from, $to] = $this->suggestedAddresses($this->direction === EmailMessage::INBOUND ? EmailMessage::OUTBOUND : EmailMessage::INBOUND);

        if (trim((string) $this->from_address) === (string) $from && trim((string) $this->to) === (string) $to) {
            [$this->from_address, $this->to] = $this->suggestedAddresses($this->direction);
        }
    }

    /**
     * Fill the form from an email pasted into the body with its header block
     * (From / Sent / To / Cc / Subject). Text without one is left alone.
     */
    public function fillFromPaste(PastedEmailParser $parser, EmailHtmlSanitizer $sanitizer, EmailMessageService $emailMessageService): void
    {
        $this->authorize('create', EmailMessage::class);

        $parsed = $parser->parse($sanitizer->toText($this->body_html));

        if (! $parsed) {
            $this->warning(ucfirst(trans('laravel-crm::lang.email_headers_not_found')));

            return;
        }

        $this->from_name = $parsed['from_name'];
        $this->from_address = $parsed['from_address'];
        $this->to = $parsed['to'];
        $this->cc = $parsed['cc'];
        $this->subject = $parsed['subject'];
        $this->attachment_names = $parsed['attachment_names'];
        $this->body_html = $sanitizer->fromText($parsed['body']);
        $this->direction = $emailMessageService->directionFor($parsed['from_address']);
        $this->source = 'paste';

        if ($parsed['sent_at']) {
            $this->sent_at = $parsed['sent_at']->format('Y-m-d\TH:i');
        }

        $this->resetValidation();
    }

    public function save(EmailMessageService $emailMessageService, EmailHtmlSanitizer $sanitizer): void
    {
        $this->authorize('create', EmailMessage::class);

        $this->validate([
            'direction' => 'required|in:'.EmailMessage::OUTBOUND.','.EmailMessage::INBOUND,
            'sent_at' => 'required|date',
            'from_address' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'to' => ['required', 'string', 'max:2000', $this->containsAnAddress()],
            'cc' => 'nullable|string|max:2000',
            'subject' => 'required|string|max:998',
            'body_html' => ['required', 'string', fn ($attribute, $value, $fail) => $sanitizer->toText($value) === ''
                ? $fail(__('validation.required', ['attribute' => __('laravel-crm::lang.body')]))
                : null],
            'attachment_names' => 'nullable|string|max:2000',
            'guests' => 'array',
        ], [], $this->attributeNames());

        $emailMessageService->create($this->model, [
            'direction' => $this->direction,
            'source' => $this->source,
            'sent_at' => $this->sent_at,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'to' => $this->to,
            'cc' => $this->cc,
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'attachment_names' => $this->attachment_names,
        ], $this->guests);

        $this->dispatch('email-message-added');
        $this->dispatch('activity-logged');

        $this->success(ucfirst(trans('laravel-crm::lang.email_logged')));

        $this->resetFields();
    }

    #[On('email-message-updated')]
    #[On('email-message-added')]
    public function getEmailMessages(): void
    {
        $this->emailMessages = collect();
        $this->data = [];

        if (! auth()->user()?->can('viewAny', EmailMessage::class)) {
            return;
        }

        $ids = $this->ownEmailIds();
        $relatedIds = [];

        foreach ($this->relatedRecords() as $record) {
            $new = array_diff($record->emailMessages()->pluck('id')->all(), $ids);

            $ids = array_merge($ids, $new);
            $relatedIds = array_merge($relatedIds, $new);
        }

        if ($ids === []) {
            return;
        }

        $this->emailMessages = EmailMessage::whereIn('id', array_unique($ids))
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get();

        foreach ($this->emailMessages as $emailMessage) {
            $this->data[$emailMessage->id] = [
                'related' => in_array($emailMessage->id, $relatedIds)
                    || $emailMessage->emailable_type !== $this->model->getMorphClass()
                    || (int) $emailMessage->emailable_id !== (int) $this->model->getKey(),
            ];
        }
    }

    /**
     * Emails logged against this record, and emails logged elsewhere that
     * name this record among the people involved.
     *
     * @return array<int, int>
     */
    protected function ownEmailIds(): array
    {
        $ids = $this->model->emailMessages()->pluck('id')->all();

        if (! method_exists($this->model, 'getMorphClass')) {
            return $ids;
        }

        $linked = EmailMessage::whereHas('contacts', fn ($query) => $query
            ->where('entityable_type', $this->model->getMorphClass())
            ->where('entityable_id', $this->model->getKey()))->pluck('id')->all();

        return array_merge($ids, $linked);
    }

    /**
     * The records whose emails are shown here as related activity: this
     * record's contacts, organisation and person. None when the setting is off.
     *
     * @return array<int, object>
     */
    protected function relatedRecords(): array
    {
        if (app('laravel-crm.settings')->get('show_related_activity') != 1) {
            return [];
        }

        $related = [];

        if (method_exists($this->model, 'contacts')) {
            foreach ($this->model->contacts as $contact) {
                if ($contact->entityable && method_exists($contact->entityable, 'emailMessages')) {
                    $related[] = $contact->entityable;
                }
            }
        }

        foreach (['organization', 'person'] as $relation) {
            if (method_exists($this->model, $relation) && $this->model->{$relation}) {
                $related[] = $this->model->{$relation};
            }
        }

        return $related;
    }

    /**
     * From and To for a direction: the user and the record's contact
     * address, one way round or the other.
     *
     * @return array{0:?string, 1:?string}
     */
    protected function suggestedAddresses(string $direction): array
    {
        $mine = auth()->user()->email ?? null;
        $theirs = app(EmailMessageService::class)->contactEmailFor($this->model);

        return $direction === EmailMessage::INBOUND ? [$theirs, $mine] : [$mine, $theirs];
    }

    protected function authorizeGuestSearch(): void
    {
        $this->authorize('create', EmailMessage::class);
    }

    protected function containsAnAddress(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (PastedEmailParser::addresses($value) === []) {
                $fail(__('validation.email', ['attribute' => __('laravel-crm::lang.to')]));
            }
        };
    }

    /**
     * @return array<string, string>
     */
    protected function attributeNames(): array
    {
        return [
            'sent_at' => __('laravel-crm::lang.date'),
            'from_address' => __('laravel-crm::lang.from'),
            'to' => __('laravel-crm::lang.to'),
            'cc' => __('laravel-crm::lang.cc'),
            'subject' => __('laravel-crm::lang.subject'),
            'body_html' => __('laravel-crm::lang.body'),
        ];
    }

    private function resetFields(): void
    {
        $this->reset('from_name', 'cc', 'subject', 'body_html', 'attachment_names', 'guests', 'guestOptions');

        $this->direction = EmailMessage::OUTBOUND;
        $this->source = 'manual';
        $this->sent_at = now()->format('Y-m-d\TH:i');

        [$this->from_address, $this->to] = $this->suggestedAddresses($this->direction);
    }

    public function render()
    {
        $this->getEmailMessages();

        return view('laravel-crm::livewire.email-messages.email-message-related');
    }
}
