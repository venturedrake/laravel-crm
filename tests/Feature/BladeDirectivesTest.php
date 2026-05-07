<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use VentureDrake\LaravelCrm\Tests\TestCase;

class BladeDirectivesTest extends TestCase
{
    private function evaluateDirective(string $directive): string
    {
        $compiled = Blade::compileString("@{$directive}() YES @end{$directive} NO");

        ob_start();
        eval('?>'.$compiled);

        return preg_replace('/\s+/', '', (string) ob_get_clean());
    }

    public function test_module_directives_are_true_when_modules_config_is_empty(): void
    {
        config()->set('laravel-crm.modules', null);

        $this->assertSame('YESNO', $this->evaluateDirective('hasleadsenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasdealsenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasquotesenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasordersenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasinvoicesenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasdeliveriesenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('haspurchaseordersenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasteamsenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('haschatenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasemailmarketingenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hassmsmarketingenabled'));
    }

    public function test_module_directives_match_modules_array(): void
    {
        config()->set('laravel-crm.modules', ['leads', 'deals']);

        $this->assertSame('YESNO', $this->evaluateDirective('hasleadsenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasdealsenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hasquotesenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hasinvoicesenabled'));
        $this->assertSame('NO', $this->evaluateDirective('haschatenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hasemailmarketingenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hassmsmarketingenabled'));
    }

    public function test_chat_directive_is_true_when_chat_module_enabled(): void
    {
        config()->set('laravel-crm.modules', ['chat']);

        $this->assertSame('YESNO', $this->evaluateDirective('haschatenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hasleadsenabled'));
    }

    public function test_email_marketing_directive_is_true_when_module_enabled(): void
    {
        config()->set('laravel-crm.modules', ['email-marketing']);

        $this->assertSame('YESNO', $this->evaluateDirective('hasemailmarketingenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hassmsmarketingenabled'));
    }

    public function test_sms_marketing_directive_is_true_when_module_enabled(): void
    {
        config()->set('laravel-crm.modules', ['sms-marketing']);

        $this->assertSame('YESNO', $this->evaluateDirective('hassmsmarketingenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hasemailmarketingenabled'));
    }
}
