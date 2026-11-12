<?php

use VentureDrake\LaravelCrm\Support\EmailHtmlSanitizer;

/*
 * A logged email's body is pasted from a mail client, so it is cleaned with
 * an allowlist before it is stored and again before it is shown.
 */

beforeEach(fn () => $this->sanitizer = new EmailHtmlSanitizer);

it('keeps formatting and drops everything that could run or load', function () {
    $clean = $this->sanitizer->clean(
        '<p style="color:red" onclick="steal()">Hi <b>Sam</b>,</p>'
        .'<script>alert(1)</script><style>p{display:none}</style>'
        .'<img src="https://tracker.example/pixel.gif" onerror="alert(2)">'
        .'<iframe src="https://evil.example"></iframe>'
        .'<ul><li>One</li><li>Two</li></ul>'
    );

    expect($clean)->toBe('<p>Hi <b>Sam</b>,</p><ul><li>One</li><li>Two</li></ul>');
});

it('keeps safe links and neutralises unsafe ones', function () {
    $clean = $this->sanitizer->clean(
        '<a href="https://example.com/x" onclick="x()">site</a> '
        .'<a href="javascript:alert(1)">bad</a> '
        .'<a href="mailto:sam@example.org">mail</a>'
    );

    expect($clean)->toContain('<a href="https://example.com/x" target="_blank" rel="noopener noreferrer nofollow">site</a>')
        ->toContain('<a>bad</a>')
        ->toContain('href="mailto:sam@example.org"')
        ->not->toContain('javascript')
        ->not->toContain('onclick');
});

it('unwraps unknown tags and keeps their text', function () {
    expect($this->sanitizer->clean('<font face="Arial"><o:p>Kept</o:p></font>'))->toBe('Kept');
});

it('escapes plain text that looks like markup', function () {
    expect($this->sanitizer->fromText("1 < 2 & 3\nnext line\n\nNew paragraph"))
        ->toBe('<p>1 &lt; 2 &amp; 3<br>'."\n".'next line</p><p>New paragraph</p>');
});

it('turns a body into plain text with line breaks', function () {
    expect($this->sanitizer->toText('<p>Hi Sam,</p><p>Line one<br>Line two</p><ul><li>A</li><li>B</li></ul>'))
        ->toBe("Hi Sam,\nLine one\nLine two\nA\nB");
});

it('treats an empty or markup-only body as empty', function () {
    expect($this->sanitizer->clean(null))->toBe('')
        ->and($this->sanitizer->toText('<p><br></p><script>x</script>'))->toBe('');
});

it('keeps accented and non-latin text intact', function () {
    expect($this->sanitizer->clean('<p>Café — 日本語</p>'))->toBe('<p>Café — 日本語</p>');
});

it('drops a rule that opens the body and keeps one further down', function () {
    $sanitizer = new EmailHtmlSanitizer;

    expect($sanitizer->clean('<div> <hr><hr><p>From: a</p><hr><p>b</p></div>'))->toBe('<div> <p>From: a</p><hr><p>b</p></div>')
        ->and($sanitizer->clean('<hr>'))->toBe('')
        ->and($sanitizer->clean('<p>a</p><hr>'))->toBe('<p>a</p><hr>');
});
