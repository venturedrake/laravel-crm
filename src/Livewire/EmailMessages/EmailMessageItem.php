<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailMessages;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasGuestSearch;
use VentureDrake\LaravelCrm\Models\EmailMessage;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\EmailMessageService;
use VentureDrake\LaravelCrm\Support\EmailHtmlSanitizer;
use VentureDrake\LaravelCrm\Support\PastedEmailParser;

/**
 * One logged email, in the Emails tab and on the timeline.
 *
 * The property is `$emailmessage`, all lower case, because the timeline
 * mounts `crm-{lower-cased class name}-item` and passes the record under
 * that same lower-cased name.
 */
class EmailMessageItem extends Component
{
    use AuthorizesRequests, HasGuestSearch, Toast;

    public EmailMessage $emailmessage;

    public bool $related = false;

    public bool $editing = false;

    /**
     * Whether the email is opened to its full details. Bound to the collapse
     * widget, so it survives the list around the item being refreshed.
     */
    public bool $expanded = false;

    public string $direction = EmailMessage::OUTBOUND;

    public ?string $sent_at = null;

    public ?string $from_address = null;

    public ?string $from_name = null;

    public ?string $to = null;

    public ?string $cc = null;

    public ?string $subject = null;

    public ?string $body_html = null;

    public ?string $attachment_names = null;

    public array $guests = [];

    public function mount(EmailMessage $emailmessage, bool $related = false): void
    {
        $this->emailmessage = $emailmessage;
        $this->related = $related;

        $this->hydrateFromRecord();
    }

    /**
     * Fill the form fields from the stored record. Cancelling reads the
     * record back rather than restoring a snapshot, as the other activity
     * items do.
     */
    private function hydrateFromRecord(): void
    {
        $this->direction = $this->emailmessage->isOutbound() ? EmailMessage::OUTBOUND : EmailMessage::INBOUND;
        $this->sent_at = $this->emailmessage->sent_at?->format('Y-m-d\TH:i');
        $this->from_address = $this->emailmessage->from_address;
        $this->from_name = $this->emailmessage->from_name;
        $this->to = $this->emailmessage->to;
        $this->cc = $this->emailmessage->cc;
        $this->subject = $this->emailmessage->subject;
        $this->body_html = $this->emailmessage->body_html;
        $this->attachment_names = $this->emailmessage->attachment_names;
        $this->guests = $this->emailmessage->contacts()
            ->where('entityable_type', (new Person)->getMorphClass())
            ->pluck('entityable_id')
            ->toArray();
    }

    public function edit(): void
    {
        $this->loadSelectedGuests();
        $this->editing = true;
        $this->expanded = true;
    }

    protected function authorizeGuestSearch(): void
    {
        $this->authorize('update', $this->emailmessage);
    }

    public function cancel(): void
    {
        $this->hydrateFromRecord();
        $this->resetValidation();
        $this->editing = false;
    }

    /**
     * Another copy of this email on the page was changed (it is shown in the
     * Emails tab and on the timeline): show what was saved, unless it is
     * being edited here.
     */
    #[On('email-message-updated.{emailmessage.id}')]
    public function refreshFromRecord(): void
    {
        if (! $this->editing) {
            $this->hydrateFromRecord();
        }
    }

    public function update(EmailMessageService $emailMessageService, EmailHtmlSanitizer $sanitizer): void
    {
        $this->authorize('update', $this->emailmessage);

        $this->validate([
            'direction' => 'required|in:'.EmailMessage::OUTBOUND.','.EmailMessage::INBOUND,
            'sent_at' => 'required|date',
            'from_address' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'to' => ['required', 'string', 'max:2000', fn ($attribute, $value, $fail) => PastedEmailParser::addresses($value) === []
                ? $fail(__('validation.email', ['attribute' => __('laravel-crm::lang.to')]))
                : null],
            'cc' => 'nullable|string|max:2000',
            'subject' => 'required|string|max:998',
            'body_html' => ['required', 'string', fn ($attribute, $value, $fail) => $sanitizer->toText($value) === ''
                ? $fail(__('validation.required', ['attribute' => __('laravel-crm::lang.body')]))
                : null],
            'attachment_names' => 'nullable|string|max:2000',
            'guests' => 'array',
        ], [], [
            'sent_at' => __('laravel-crm::lang.date'),
            'from_address' => __('laravel-crm::lang.from'),
            'to' => __('laravel-crm::lang.to'),
            'cc' => __('laravel-crm::lang.cc'),
            'subject' => __('laravel-crm::lang.subject'),
            'body_html' => __('laravel-crm::lang.body'),
        ]);

        $emailMessageService->update($this->emailmessage, [
            'direction' => $this->direction,
            'sent_at' => $this->sent_at,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'to' => $this->to,
            'cc' => $this->cc,
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'attachment_names' => $this->attachment_names,
        ], $this->guests);

        $this->dispatch('email-message-updated');
        $this->dispatch('email-message-updated.'.$this->emailmessage->id);
        $this->dispatch('activity-logged');

        $this->success(ucfirst(trans('laravel-crm::lang.email_updated')));

        $this->hydrateFromRecord();
        $this->editing = false;
    }

    public function delete(EmailMessageService $emailMessageService): void
    {
        $this->authorize('delete', $this->emailmessage);

        $emailMessageService->delete($this->emailmessage);

        $this->success(ucfirst(trans('laravel-crm::lang.email_deleted')));

        $this->dispatch('email-message-updated');
        $this->dispatch('activity-logged');
    }

    public function render()
    {
        return view('laravel-crm::livewire.email-messages.email-message-item', [
            'bodyHtml' => $this->expanded && ! $this->editing ? app(EmailHtmlSanitizer::class)->clean($this->emailmessage->body_html) : '',
        ]);
    }
}
