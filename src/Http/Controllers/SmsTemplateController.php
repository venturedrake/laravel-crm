<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\SmsTemplate;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SmsTemplate::class);

        return view('laravel-crm::sms-templates.index');
    }

    public function create()
    {
        $this->authorize('create', SmsTemplate::class);

        return view('laravel-crm::sms-templates.create');
    }

    public function show(SmsTemplate $smsTemplate)
    {
        $this->authorize('view', $smsTemplate);

        return view('laravel-crm::sms-templates.show', ['template' => $smsTemplate]);
    }

    public function edit(SmsTemplate $smsTemplate)
    {
        $this->authorize('update', $smsTemplate);

        return view('laravel-crm::sms-templates.edit', ['template' => $smsTemplate]);
    }
}
