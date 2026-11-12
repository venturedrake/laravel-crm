<?php

/*
 * Free text a user types is shown as text, not markup.
 *
 * Note content and task descriptions were echoed with {!! !!}, so a note of
 * `<img src=x onerror=alert(1)>` ran script for everyone who opened the
 * record. Both are typed into plain textareas, so they are escaped and only
 * their line breaks become <br>. The PDF templates did the same with terms,
 * descriptions, delivery instructions, contact details and payment
 * instructions - and those templates are also rendered in a browser on the
 * public portal.
 */

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Notes\NoteItem;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskItem;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Support\PdfSampleData;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

const XSS_PAYLOAD = "<img src=x onerror=alert(1)>\nsecond line";

beforeEach(function () {
    $this->actingAsUserWithPermissions(['view crm people', 'view crm notes', 'view crm tasks']);
    $this->person = Person::create(['first_name' => 'Payload']);
});

test('a note is shown as text', function () {
    // The observer skips user_created_id in the console; the view names the author.
    $note = $this->person->notes()->create(['content' => XSS_PAYLOAD, 'user_created_id' => auth()->id()]);

    // Collapsed, the note shows a one-line summary; opened, the whole text.
    Livewire::test(NoteItem::class, ['note' => $note])
        ->assertDontSee('<img src=x', false)
        ->set('expanded', true)
        ->assertSee('&lt;img src=x onerror=alert(1)&gt;<br />', false)
        ->assertDontSee('<img src=x', false);
});

test('a task description is shown as text', function () {
    $task = $this->person->tasks()->create(['name' => 'Payload task', 'description' => XSS_PAYLOAD]);

    Livewire::test(TaskItem::class, ['task' => $task])
        ->set('expanded', true)
        ->assertSee('&lt;img src=x onerror=alert(1)&gt;<br />', false)
        ->assertDontSee('<img src=x', false);
});

/**
 * The variables the download controllers hand a PDF template, with the
 * payload in every free-text setting.
 */
function payloadPdfData(): array
{
    return [
        'dateFormat' => 'M j, Y',
        'taxName' => 'Tax',
        'contactDetails' => XSS_PAYLOAD,
        'paymentInstructions' => XSS_PAYLOAD,
        'fromName' => 'Sample Organization',
        'logo' => null,
        'email' => null,
        'phone' => null,
        'address' => PdfSampleData::address(),
        'organization_address' => PdfSampleData::address(),
    ];
}

test('a PDF shows its terms and contact details as text', function () {
    $quote = PdfSampleData::quote();
    $quote->terms = XSS_PAYLOAD;
    $quote->description = XSS_PAYLOAD;

    $html = view(PdfTemplateRegistry::viewFor('quote', 'modern'), payloadPdfData() + ['quote' => $quote])->render();

    expect($html)->toContain('&lt;img src=x onerror=alert(1)&gt;<br />')
        ->not->toContain('<img src=x');
});

test('the classic invoice PDF shows its terms and payment instructions as text', function () {
    $invoice = PdfSampleData::invoice();
    $invoice->terms = XSS_PAYLOAD;

    $html = view(PdfTemplateRegistry::viewFor('invoice', 'classic'), payloadPdfData() + ['invoice' => $invoice])->render();

    expect($html)->toContain('&lt;img src=x onerror=alert(1)&gt;<br />')
        ->not->toContain('<img src=x');
});

test('a note on a person page is shown as text', function () {
    Gate::before(fn () => true);

    // Pinned, because the Notes tab only loads once it is opened.
    $this->person->notes()->create(['content' => XSS_PAYLOAD, 'pinned' => 1, 'user_created_id' => auth()->id()]);

    $this->get(route('laravel-crm.people.show', $this->person))
        ->assertOk()
        ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
        ->assertDontSee('<img src=x', false);
});
