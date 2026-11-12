<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Support\Carbon;

/**
 * Reads the header block of an email pasted as text:
 *
 *     From: Casey Nguyen <casey@example.com>
 *     Sent: Tuesday, 10 November 2026 10:15 AM
 *     To: Sam Lee <sam@example.org>; pat@example.org
 *     Cc: accounts@example.org
 *     Subject: Quote for the new site
 *
 *     Hi Sam, …
 *
 * This is the block Outlook shows above a message and puts above a reply or
 * a forward. Only English labels are recognised. Text without such a block
 * is not an error: parse() returns null and the caller keeps the text as the
 * body.
 */
class PastedEmailParser
{
    protected const LABELS = [
        'from' => 'from',
        'sent' => 'sent_at',
        'date' => 'sent_at',
        'to' => 'to',
        'cc' => 'cc',
        'subject' => 'subject',
        'attachments' => 'attachment_names',
    ];

    /**
     * @return array{from_name:?string, from_address:?string, to:?string, cc:?string, subject:?string, sent_at:?Carbon, attachment_names:?string, body:string}|null
     */
    public function parse(?string $text): ?array
    {
        $lines = preg_split('/\R/u', str_replace("\u{00A0}", ' ', (string) $text));

        $fields = [];
        $current = null;
        $bodyStart = null;

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);

            // Skip blank lines and the "-----Original Message-----" style
            // rule before the block starts.
            if ($fields === [] && ($trimmed === '' || preg_match('/^[-_=]{3,}.*$/', $trimmed))) {
                continue;
            }

            if (preg_match('/^(from|sent|date|to|cc|subject|attachments)\s*:\s*(.*)$/i', $trimmed, $match)) {
                $current = self::LABELS[strtolower($match[1])];
                $fields[$current] ??= trim($match[2]);

                continue;
            }

            // A blank line ends the block, unless another header follows it:
            // the block Outlook puts above a reply has each header in its
            // own paragraph, which pastes with a blank line between them.
            if ($trimmed === '') {
                if ($this->headerFollows($lines, $index + 1)) {
                    continue;
                }

                $bodyStart = $index + 1;

                break;
            }

            // A wrapped header value continues on the next line.
            if ($current !== null && $fields !== [] && preg_match('/^\s+\S/', $line)) {
                $fields[$current] = trim($fields[$current].' '.$trimmed);

                continue;
            }

            // Anything else before a header was found means there is no block.
            if ($fields === []) {
                return null;
            }

            $bodyStart = $index;

            break;
        }

        // A block needs a sender and at least one of the other headers,
        // otherwise a body that happens to start with "To: …" would match.
        if (! isset($fields['from']) || count($fields) < 2) {
            return null;
        }

        [$fromName, $fromAddress] = $this->splitAddress($fields['from']);

        return [
            'from_name' => $fromName,
            'from_address' => $fromAddress,
            'to' => $this->addressList($fields['to'] ?? null),
            'cc' => $this->addressList($fields['cc'] ?? null),
            'subject' => ($fields['subject'] ?? '') !== '' ? $fields['subject'] : null,
            'sent_at' => $this->date($fields['sent_at'] ?? null),
            'attachment_names' => $this->addressList($fields['attachment_names'] ?? null),
            'body' => trim(implode("\n", $bodyStart === null ? [] : array_slice($lines, $bodyStart))),
        ];
    }

    /**
     * Whether the next line with text on it is a header.
     *
     * @param  array<int, string>  $lines
     */
    protected function headerFollows(array $lines, int $from): bool
    {
        for ($index = $from; $index < count($lines); $index++) {
            $trimmed = trim($lines[$index]);

            if ($trimmed !== '') {
                return (bool) preg_match('/^(from|sent|date|to|cc|subject|attachments)\s*:/i', $trimmed);
            }
        }

        return false;
    }

    /**
     * Every email address in a string, lower-cased.
     *
     * @return array<int, string>
     */
    public static function addresses(?string $value): array
    {
        preg_match_all('/[A-Z0-9._%+\'-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', (string) $value, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[0])));
    }

    /**
     * "Casey Nguyen <casey@example.com>" as ['Casey Nguyen', 'casey@example.com'].
     *
     * @return array{0:?string, 1:?string}
     */
    protected function splitAddress(string $value): array
    {
        $address = self::addresses($value)[0] ?? null;

        $name = trim(preg_replace('/[<\[(]?[A-Z0-9._%+\'-]+@[A-Z0-9.-]+\.[A-Z]{2,}[>\])]?/i', '', $value), " \t\"'");

        return [$name !== '' ? $name : null, $address];
    }

    /**
     * Outlook separates recipients with semicolons; store them with commas.
     */
    protected function addressList(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return collect(explode(';', $value))->map(fn ($part) => trim($part))->filter()->implode(', ');
    }

    protected function date(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
