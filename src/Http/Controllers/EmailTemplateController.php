<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use VentureDrake\LaravelCrm\Models\EmailTemplate;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', EmailTemplate::class);

        return view('laravel-crm::email-templates.index');
    }

    public function create()
    {
        $this->authorize('create', EmailTemplate::class);

        return view('laravel-crm::email-templates.create');
    }

    public function show(EmailTemplate $emailTemplate)
    {
        $this->authorize('view', $emailTemplate);

        return view('laravel-crm::email-templates.show', ['template' => $emailTemplate]);
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        $this->authorize('update', $emailTemplate);

        return view('laravel-crm::email-templates.edit', ['template' => $emailTemplate]);
    }
}
