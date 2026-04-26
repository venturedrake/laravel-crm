<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Tests\TestCase;
use VentureDrake\LaravelCrm\View\Composers\SettingsComposer;

class SettingsComposerTest extends TestCase
{
    public function test_default_values_are_used_when_settings_missing(): void
    {
        $rendered = view()->make('laravel-crm::leads.index')
            ->with('leads', collect());

        // Trigger composer
        $rendered->render = null;

        // Resolve composer parameters via the static cache it populates
        SettingsComposer::$cachedParameters = null;
        $composer = new SettingsComposer;
        $composer->compose(view('laravel-crm::leads.index'));

        $this->assertSame('Y-m-d', SettingsComposer::$cachedParameters['crmDateFormat']);
        $this->assertSame('H:i', SettingsComposer::$cachedParameters['crmTimeFormat']);
        $this->assertSame('UTC', SettingsComposer::$cachedParameters['crmTimezone']);
        $this->assertSame('Tax', SettingsComposer::$cachedParameters['crmTaxName']);
        $this->assertSame('true', SettingsComposer::$cachedParameters['crmDynamicProducts']);
    }

    public function test_settings_table_values_override_defaults(): void
    {
        app('laravel-crm.settings')->set('date_format', 'd/m/Y');
        app('laravel-crm.settings')->set('time_format', 'g:i A');
        app('laravel-crm.settings')->set('tax_name', 'GST');

        SettingsComposer::$cachedParameters = null;
        $composer = new SettingsComposer;
        $composer->compose(view('laravel-crm::leads.index'));

        $this->assertSame('d/m/Y', SettingsComposer::$cachedParameters['crmDateFormat']);
        $this->assertSame('g:i A', SettingsComposer::$cachedParameters['crmTimeFormat']);
        $this->assertSame('GST', SettingsComposer::$cachedParameters['crmTaxName']);
    }
}
