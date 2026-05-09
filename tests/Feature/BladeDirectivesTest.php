<?php

use Illuminate\Support\Facades\Blade;

function evaluateDirective(string $directive): string
{
    $compiled = Blade::compileString("@{$directive}() YES @end{$directive} NO");

    ob_start();
    eval('?>'.$compiled);

    return preg_replace('/\s+/', '', (string) ob_get_clean());
}

test('module directives are true when modules config is empty', function () {
    config()->set('laravel-crm.modules', null);

    expect(evaluateDirective('hasleadsenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasdealsenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasquotesenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasordersenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasinvoicesenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasdeliveriesenabled'))->toBe('YESNO');
    expect(evaluateDirective('haspurchaseordersenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasteamsenabled'))->toBe('YESNO');
    expect(evaluateDirective('haschatenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasemailmarketingenabled'))->toBe('YESNO');
    expect(evaluateDirective('hassmsmarketingenabled'))->toBe('YESNO');
});

test('module directives match modules array', function () {
    config()->set('laravel-crm.modules', ['leads', 'deals']);

    expect(evaluateDirective('hasleadsenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasdealsenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasquotesenabled'))->toBe('NO');
    expect(evaluateDirective('hasinvoicesenabled'))->toBe('NO');
    expect(evaluateDirective('haschatenabled'))->toBe('NO');
    expect(evaluateDirective('hasemailmarketingenabled'))->toBe('NO');
    expect(evaluateDirective('hassmsmarketingenabled'))->toBe('NO');
});

test('chat directive is true when chat module enabled', function () {
    config()->set('laravel-crm.modules', ['chat']);

    expect(evaluateDirective('haschatenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasleadsenabled'))->toBe('NO');
});

test('email marketing directive is true when module enabled', function () {
    config()->set('laravel-crm.modules', ['email-marketing']);

    expect(evaluateDirective('hasemailmarketingenabled'))->toBe('YESNO');
    expect(evaluateDirective('hassmsmarketingenabled'))->toBe('NO');
});

test('sms marketing directive is true when module enabled', function () {
    config()->set('laravel-crm.modules', ['sms-marketing']);

    expect(evaluateDirective('hassmsmarketingenabled'))->toBe('YESNO');
    expect(evaluateDirective('hasemailmarketingenabled'))->toBe('NO');
});
