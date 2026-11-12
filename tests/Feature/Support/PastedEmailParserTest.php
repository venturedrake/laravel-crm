<?php

use VentureDrake\LaravelCrm\Support\PastedEmailParser;

/*
 * The header block Outlook shows above a message, pasted as text, fills the
 * Log email form.
 */

beforeEach(fn () => $this->parser = new PastedEmailParser);

it('reads the header block and the body', function () {
    $parsed = $this->parser->parse(<<<'TEXT'
        From: Casey Nguyen <casey@example.com>
        Sent: Tuesday, 10 November 2026 10:15 AM
        To: Sam Lee <sam@example.org>; pat@example.org
        Cc: accounts@example.org
        Subject: Quote for the new site
        Attachments: quote.pdf; plan.dwg

        Hi Sam,

        Quote attached.
        TEXT);

    expect($parsed['from_name'])->toBe('Casey Nguyen')
        ->and($parsed['from_address'])->toBe('casey@example.com')
        ->and($parsed['to'])->toBe('Sam Lee <sam@example.org>, pat@example.org')
        ->and($parsed['cc'])->toBe('accounts@example.org')
        ->and($parsed['subject'])->toBe('Quote for the new site')
        ->and($parsed['attachment_names'])->toBe('quote.pdf, plan.dwg')
        ->and($parsed['sent_at']->format('Y-m-d H:i'))->toBe('2026-11-10 10:15')
        ->and($parsed['body'])->toBe("Hi Sam,\n\nQuote attached.");
});

it('accepts Date in place of Sent, a bare address and a leading rule', function () {
    $parsed = $this->parser->parse("\n-----Original Message-----\nFrom: casey@example.com\nDate: 2026-11-10 08:00\nSubject: Hello\n\nBody");

    expect($parsed['from_name'])->toBeNull()
        ->and($parsed['from_address'])->toBe('casey@example.com')
        ->and($parsed['to'])->toBeNull()
        ->and($parsed['sent_at']->format('Y-m-d H:i'))->toBe('2026-11-10 08:00')
        ->and($parsed['body'])->toBe('Body');
});

it('leaves an unreadable date empty', function () {
    $parsed = $this->parser->parse("From: casey@example.com\nSent: sometime last week-ish\nSubject: Hello\n\nBody");

    expect($parsed['sent_at'])->toBeNull()
        ->and($parsed['subject'])->toBe('Hello');
});

it('returns null for text without a header block', function (string $text) {
    expect($this->parser->parse($text))->toBeNull();
})->with([
    'plain body' => ["Hi Sam,\n\nSee you Tuesday."],
    'empty' => [''],
    'a lone To line' => ["To: everyone\n\nPlease read."],
    'a header later in the text' => ["Hi Sam,\nFrom: casey@example.com\nSubject: x"],
]);

it('finds every address in a recipient list', function () {
    expect(PastedEmailParser::addresses('Sam Lee <Sam@Example.org>, pat@example.org; sam@example.org'))
        ->toBe(['sam@example.org', 'pat@example.org']);
});

it('reads a reply header block that has a blank line between each header', function () {
    $parsed = (new PastedEmailParser)->parse(implode("\n", [
        'From: Mathew Example <Mathew.Example@example.com.au>',
        '',
        'Sent: Tuesday, October 6, 2026 4:53 PM',
        '',
        'To: Wayne Sample <wsample@example.org>',
        '',
        'Cc: Justin Sample <jsample@example.org>; Ben Example <ben.example@example.com.au>',
        '',
        'Subject: Re: Independent Delivery Assurance Presentation',
        ' ',
        'Hi Wayne,',
        'I hope the week is running smoothly.',
        '',
        'To: be clear, this line is part of the body.',
    ]));

    expect($parsed)->not->toBeNull()
        ->and($parsed['from_name'])->toBe('Mathew Example')
        ->and($parsed['from_address'])->toBe('mathew.example@example.com.au')
        ->and($parsed['to'])->toBe('Wayne Sample <wsample@example.org>')
        ->and($parsed['cc'])->toBe('Justin Sample <jsample@example.org>, Ben Example <ben.example@example.com.au>')
        ->and($parsed['subject'])->toBe('Re: Independent Delivery Assurance Presentation')
        ->and($parsed['sent_at']->format('Y-m-d H:i'))->toBe('2026-10-06 16:53')
        ->and($parsed['body'])->toStartWith('Hi Wayne,')
        ->and($parsed['body'])->toContain('To: be clear');
});
