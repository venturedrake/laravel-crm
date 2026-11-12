<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\EmailMessage;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Support\EmailHtmlSanitizer;
use VentureDrake\LaravelCrm\Support\PastedEmailParser;

class EmailMessageService
{
    public function __construct(protected EmailHtmlSanitizer $sanitizer) {}

    /**
     * Log an email against a record and put it on the timeline.
     *
     * @param  array{direction?:string, source?:string, from_address?:?string, from_name?:?string, to?:?string, cc?:?string, subject?:?string, body_html?:?string, attachment_names?:?string, sent_at?:mixed}  $data
     * @param  array<int, int|string>  $personIds  other people involved
     */
    public function create(Model $record, array $data, array $personIds = []): EmailMessage
    {
        return DB::transaction(function () use ($record, $data, $personIds) {
            $emailMessage = new EmailMessage($this->attributes($data));
            $emailMessage->source = $data['source'] ?? 'manual';
            $emailMessage->emailable_type = $record->getMorphClass();
            $emailMessage->emailable_id = $record->getKey();
            $emailMessage->user_owner_id = auth()->id();
            $emailMessage->save();

            $this->logActivity($emailMessage, $record);
            $this->syncPeople($emailMessage, $personIds);

            return $emailMessage;
        });
    }

    /**
     * @param  array<int, int|string>|null  $personIds  null leaves the people involved as they are
     */
    public function update(EmailMessage $emailMessage, array $data, ?array $personIds = null): EmailMessage
    {
        return DB::transaction(function () use ($emailMessage, $data, $personIds) {
            $emailMessage->fill($this->attributes($data));
            $emailMessage->save();

            if ($personIds !== null) {
                $this->syncPeople($emailMessage, $personIds);
            }

            return $emailMessage;
        });
    }

    public function delete(EmailMessage $emailMessage): void
    {
        $emailMessage->delete();
    }

    /**
     * Sent when the sender is a CRM user, received otherwise.
     */
    public function directionFor(?string $fromAddress): string
    {
        $address = PastedEmailParser::addresses($fromAddress)[0] ?? null;

        if (! $address || ! auth()->user()) {
            return EmailMessage::INBOUND;
        }

        $isUser = auth()->user()->newQuery()->whereRaw('LOWER(email) = ?', [$address])->exists();

        return $isUser ? EmailMessage::OUTBOUND : EmailMessage::INBOUND;
    }

    /**
     * The email address to suggest for a record: its own primary address, or
     * that of the person or organisation it belongs to.
     */
    public function contactEmailFor(?Model $record, int $depth = 0): ?string
    {
        if (! $record || $depth > 1) {
            return null;
        }

        if (method_exists($record, 'emails')) {
            $email = $record->emails()->orderByDesc('primary')->orderBy('id')->first();

            if ($email?->address) {
                return $email->address;
            }
        }

        foreach (['person', 'organization'] as $relation) {
            if (method_exists($record, $relation) && $address = $this->contactEmailFor($record->{$relation}, $depth + 1)) {
                return $address;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        $attributes = $this->textAttributes($data);

        if (array_key_exists('direction', $data)) {
            $attributes['direction'] = $data['direction'] === EmailMessage::INBOUND ? EmailMessage::INBOUND : EmailMessage::OUTBOUND;
        }

        if (array_key_exists('body_html', $data)) {
            $html = $this->sanitizer->clean($data['body_html']);

            $attributes['body_html'] = $html !== '' ? $html : null;
            $attributes['body_text'] = $html !== '' ? $this->sanitizer->toText($html) : null;
        }

        if (array_key_exists('sent_at', $data)) {
            $attributes['sent_at'] = $data['sent_at'] ? Carbon::parse(str_replace('T', ' ', (string) $data['sent_at'])) : now();
        }

        return $attributes;
    }

    /**
     * The plain text fields that were given, trimmed, with an empty one as null.
     *
     * @return array<string, ?string>
     */
    protected function textAttributes(array $data): array
    {
        $attributes = [];

        foreach (['from_address', 'from_name', 'to', 'cc', 'subject', 'attachment_names'] as $key) {
            if (array_key_exists($key, $data)) {
                $value = trim((string) $data[$key]);

                $attributes[$key] = $value !== '' ? $value : null;
            }
        }

        return $attributes;
    }

    /**
     * Link the other people involved, and show the email on each of their
     * timelines. A person the email is already logged against is skipped.
     *
     * @param  array<int, int|string>  $personIds
     */
    protected function syncPeople(EmailMessage $emailMessage, array $personIds): void
    {
        $people = Person::whereIn('id', array_map('intval', $personIds))->get()
            ->reject(fn (Person $person) => $emailMessage->emailable_type === $person->getMorphClass()
                && (int) $emailMessage->emailable_id === (int) $person->id);

        $morphClass = (new Person)->getMorphClass();
        $keep = $people->pluck('id')->all();

        $emailMessage->contacts()
            ->where('entityable_type', $morphClass)
            ->whereNotIn('entityable_id', $keep)
            ->get()->each->delete();

        $emailMessage->activities()
            ->where('timelineable_type', $morphClass)
            ->whereNotIn('timelineable_id', $keep)
            ->where(fn ($query) => $query
                ->where('timelineable_type', '!=', $emailMessage->emailable_type)
                ->orWhere('timelineable_id', '!=', $emailMessage->emailable_id))
            ->get()->each->delete();

        foreach ($people as $person) {
            $emailMessage->contacts()->firstOrCreate([
                'entityable_type' => $person->getMorphClass(),
                'entityable_id' => $person->id,
            ]);

            $this->logActivity($emailMessage, $person);
        }
    }

    protected function logActivity(EmailMessage $emailMessage, Model $record): void
    {
        $emailMessage->activities()->firstOrCreate([
            'timelineable_type' => $record->getMorphClass(),
            'timelineable_id' => $record->getKey(),
        ], [
            'causeable_type' => auth()->user()?->getMorphClass(),
            'causeable_id' => auth()->id(),
        ]);
    }
}
