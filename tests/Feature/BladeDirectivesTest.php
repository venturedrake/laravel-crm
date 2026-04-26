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
    }

    public function test_module_directives_match_modules_array(): void
    {
        config()->set('laravel-crm.modules', ['leads', 'deals']);

        $this->assertSame('YESNO', $this->evaluateDirective('hasleadsenabled'));
        $this->assertSame('YESNO', $this->evaluateDirective('hasdealsenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hasquotesenabled'));
        $this->assertSame('NO', $this->evaluateDirective('hasinvoicesenabled'));
    }
}
