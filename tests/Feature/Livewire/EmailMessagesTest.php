<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Activities\ActivityFeed;
use VentureDrake\LaravelCrm\Livewire\Activities\ActivityIndex;
use VentureDrake\LaravelCrm\Livewire\ActivityMenu;
use VentureDrake\LaravelCrm\Livewire\EmailMessages\EmailMessageItem;
use VentureDrake\LaravelCrm\Livewire\EmailMessages\EmailMessageRelated;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\EmailMessage;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\EmailMessageService;

/*
 * The Log email button, the Emails tab and a logged email on the timeline.
 */

const EMAIL_PERMISSIONS = ['create crm emails', 'view crm emails', 'edit crm emails', 'delete crm emails'];

beforeEach(function () {
    $this->user = $this->actingAsUserWithPermissions(EMAIL_PERMISSIONS, ['email' => 'me@crm.test']);
    $this->sam = Person::create(['first_name' => 'Sam', 'last_name' => 'Lee']);

    Email::create([
        'address' => 'sam@example.org', 'primary' => true,
        'emailable_type' => $this->sam->getMorphClass(), 'emailable_id' => $this->sam->id,
    ]);
});

function loggedEmail(array $data = [], array $people = [], $record = null): EmailMessage
{
    return app(EmailMessageService::class)->create($record ?? test()->sam, array_merge([
        'direction' => 'outbound',
        'sent_at' => '2026-11-10T10:15',
        'from_address' => 'me@crm.test',
        'to' => 'sam@example.org',
        'subject' => 'Quote for the new site',
        'body_html' => '<p>Hi <b>Sam</b>, quote attached.</p>',
    ], $data), $people);
}

it('opens the Emails tab from the envelope button', function () {
    Livewire::test(ActivityMenu::class)
        ->assertSeeHtml('wire:click.prevent="addEmail()"')
        ->call('addEmail')
        ->assertDispatched('select-activity-tab', 'emails');
});

it('hides the envelope button without the create permission', function () {
    $this->actingAsUserWithPermissions(['view crm emails']);

    Livewire::test(ActivityMenu::class)->assertDontSeeHtml('addEmail()');
});

it('prefills a sent email from me to the record, and swaps for a received one', function () {
    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->assertSet('direction', 'outbound')
        ->assertSet('from_address', 'me@crm.test')
        ->assertSet('to', 'sam@example.org')
        ->set('direction', 'inbound')
        ->assertSet('from_address', 'sam@example.org')
        ->assertSet('to', 'me@crm.test');
});

it('keeps addresses the user typed when the direction changes', function () {
    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->set('to', 'someone.else@example.org')
        ->set('direction', 'inbound')
        ->assertSet('to', 'someone.else@example.org')
        ->assertSet('from_address', 'me@crm.test');
});

it('logs a sent email and lists it', function () {
    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->set('subject', 'Quote for the new site')
        ->set('body_html', '<p>Hi <b>Sam</b></p><script>alert(1)</script>')
        ->set('sent_at', '2026-11-10T10:15')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('email-message-added')
        ->assertDispatched('activity-logged')
        // The form is ready for the next one.
        ->assertSet('subject', null)
        ->assertSet('body_html', null)
        ->assertSee('Quote for the new site');

    $email = EmailMessage::sole();

    expect($email->emailable->is($this->sam))->toBeTrue()
        ->and($email->direction)->toBe('outbound')
        ->and($email->source)->toBe('manual')
        ->and($email->to)->toBe('sam@example.org')
        ->and($email->body_html)->toBe('<p>Hi <b>Sam</b></p>')
        ->and($this->sam->activities()->count())->toBe(1);
});

it('requires a subject, a body, a sender and at least one recipient address', function () {
    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->set('from_address', 'not-an-address')
        ->set('to', 'Sam Lee')
        ->set('body_html', '<p><br></p>')
        ->call('save')
        ->assertHasErrors(['from_address', 'to', 'subject', 'body_html']);

    expect(EmailMessage::count())->toBe(0);
});

it('fills the form from a pasted email', function () {
    $pasted = '<div>From: Sam Lee &lt;sam@example.org&gt;</div>'
        .'<div>Sent: Tuesday, 10 November 2026 10:15 AM</div>'
        .'<div>To: Me &lt;me@crm.test&gt;; pat@example.org</div>'
        .'<div>Subject: Re: Quote</div>'
        .'<div><br></div><div>Thanks, go ahead.</div>';

    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->set('body_html', $pasted)
        ->call('fillFromPaste')
        ->assertSet('direction', 'inbound')
        ->assertSet('from_name', 'Sam Lee')
        ->assertSet('from_address', 'sam@example.org')
        ->assertSet('to', 'Me <me@crm.test>, pat@example.org')
        ->assertSet('subject', 'Re: Quote')
        ->assertSet('sent_at', '2026-11-10T10:15')
        ->assertSet('body_html', '<p>Thanks, go ahead.</p>')
        ->call('save')
        ->assertHasNoErrors();

    expect(EmailMessage::sole()->source)->toBe('paste')
        ->and(EmailMessage::sole()->direction)->toBe('inbound');
});

it('marks a pasted email as sent when the sender is a CRM user', function () {
    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->set('body_html', '<p>From: me@crm.test<br>To: sam@example.org<br>Subject: Hello</p><p>Body</p>')
        ->call('fillFromPaste')
        ->assertSet('direction', 'outbound');
});

it('leaves the form alone when the pasted text has no header block', function () {
    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->set('body_html', '<p>Just a note to self.</p>')
        ->call('fillFromPaste')
        ->assertSet('body_html', '<p>Just a note to self.</p>')
        ->assertSet('from_address', 'me@crm.test')
        ->assertSet('source', 'manual');
});

it('shows an email on the tab of each other person involved', function () {
    $pat = Person::create(['first_name' => 'Pat']);
    loggedEmail([], [$pat->id]);

    Livewire::test(EmailMessageRelated::class, ['model' => $pat])->assertSee('Quote for the new site');
});

it('shows a person\'s emails on their organisation only when related activity is on', function () {
    $organization = Organization::create(['name' => 'JumpSkipAreo']);
    $this->sam->update(['organization_id' => $organization->id]);
    $organization->contacts()->create(['entityable_type' => $this->sam->getMorphClass(), 'entityable_id' => $this->sam->id]);
    loggedEmail();

    app('laravel-crm.settings')->set('show_related_activity', 0);
    Livewire::test(EmailMessageRelated::class, ['model' => $organization])->assertDontSee('Quote for the new site');

    app('laravel-crm.settings')->set('show_related_activity', 1);
    Livewire::test(EmailMessageRelated::class, ['model' => $organization])->assertSee('Quote for the new site');
});

it('expands an email to show its cleaned body', function () {
    $email = loggedEmail(['cc' => 'accounts@example.org', 'attachment_names' => 'quote.pdf']);

    Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->assertSee('Quote for the new site')
        ->assertSee('sam@example.org')
        ->assertDontSee('quote attached.')
        ->set('expanded', true)
        ->assertSeeHtml('<p>Hi <b>Sam</b>, quote attached.</p>')
        ->assertSee('accounts@example.org')
        ->assertSee('quote.pdf');
});

it('edits an email, and cancel restores the stored values', function () {
    $email = loggedEmail();

    Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->call('edit')
        ->set('subject', 'Abandoned change')
        ->call('cancel')
        ->assertSet('subject', 'Quote for the new site')
        ->call('edit')
        ->set('subject', 'Quote v2')
        ->set('direction', 'inbound')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('editing', false)
        ->assertDispatched('email-message-updated');

    expect($email->fresh()->subject)->toBe('Quote v2')
        ->and($email->fresh()->direction)->toBe('inbound');
});

it('deletes an email', function () {
    $email = loggedEmail();

    Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->call('delete')
        ->assertDispatched('email-message-updated');

    expect(EmailMessage::find($email->id))->toBeNull()
        ->and($this->sam->activities()->count())->toBe(0);
});

it('forbids logging, pasting, editing and deleting without the matching permission', function () {
    $email = loggedEmail();
    $this->actingAsUserWithPermissions(['view crm emails']);

    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->set('subject', 'Nope')->set('body_html', '<p>x</p>')
        ->call('save')->assertForbidden();

    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->call('fillFromPaste')->assertForbidden();

    Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->set('subject', 'Tampered')->call('update')->assertForbidden();

    Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->call('delete')->assertForbidden();

    expect(EmailMessage::count())->toBe(1)
        ->and($email->fresh()->subject)->toBe('Quote for the new site');
});

it('lists no emails for a user who may not view them', function () {
    loggedEmail();
    $this->actingAsUserWithPermissions(['create crm emails']);

    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])->assertDontSee('Quote for the new site');
});

it('puts a logged email on the timeline with its own wording and date', function () {
    loggedEmail();
    loggedEmail(['direction' => 'inbound', 'subject' => 'Re: Quote', 'sent_at' => '2026-11-10T11:30']);

    Livewire::test(ActivityIndex::class, ['model' => $this->sam])
        ->assertSee('logged a sent email')
        ->assertSee('logged a received email')
        ->assertSee('11/10/2026 10:15 AM')
        ->assertSee('Re: Quote')
        ->assertDontSee('created a emailmessage');
});

it('keeps email entries off the timeline and the feed for a user who may not view them', function () {
    loggedEmail();
    $this->actingAsUserWithPermissions(['view crm notes']);

    Livewire::test(ActivityIndex::class, ['model' => $this->sam])
        ->assertDontSee('logged a sent email')
        ->assertDontSee('Quote for the new site');

    $feed = Livewire::test(ActivityFeed::class)->set('scope', 'all');

    expect($feed->instance()->tabs())->not->toHaveKey('emails');
    $feed->assertDontSee('Quote for the new site');
});

it('filters the activity feed to emails', function () {
    loggedEmail();

    $feed = Livewire::test(ActivityFeed::class)->set('scope', 'all')->set('tab', 'emails');

    expect($feed->instance()->tabs())->toHaveKey('emails')
        ->and($feed->instance()->activities()->total())->toBe(1);

    $feed->assertSee('logged a sent email');
});

it('searches other people on the server and logs the email against the ones picked', function () {
    $pat = Person::create(['first_name' => 'Pat', 'last_name' => 'Jones']);
    Person::create(['first_name' => 'Kim', 'last_name' => 'Wu']);

    $component = Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        // Nothing is loaded until the user types.
        ->assertSet('guestOptions', [])
        ->call('searchGuests', 'pat');

    expect(collect($component->get('guestOptions'))->pluck('name')->all())->toBe(['Pat Jones']);

    $component
        ->set('guests', [$pat->id])
        ->set('subject', 'Quote')
        ->set('body_html', '<p>Hi</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('guests', [])
        ->assertSet('guestOptions', []);

    expect(EmailMessage::sole()->contacts()->pluck('entityable_id')->all())->toBe([$pat->id])
        ->and($pat->activities()->count())->toBe(1);
});

it('forbids searching people from the form without the create permission', function () {
    $this->actingAsUserWithPermissions(['view crm emails']);

    Livewire::test(EmailMessageRelated::class, ['model' => $this->sam])
        ->call('searchGuests', 'pat')
        ->assertForbidden();
});

it('keeps the people already on an email in the picker when it is edited', function () {
    $pat = Person::create(['first_name' => 'Pat', 'last_name' => 'Jones']);
    $email = loggedEmail([], [$pat->id]);

    $component = Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->assertSet('guests', [$pat->id])
        ->call('edit');

    expect(collect($component->get('guestOptions'))->pluck('name')->all())->toBe(['Pat Jones']);
});

it('lists an email collapsed, with Delete on the edit form behind a typed confirmation', function () {
    $email = loggedEmail();

    $component = Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->assertSet('expanded', false)
        ->assertSeeHtml('wire:model.live="expanded"')
        ->assertDontSeeHtml('modalDeleteEmailMessageItem'.$email->id.'.showModal()')
        ->assertSeeHtml("typed.trim().toLowerCase() !== 'confirm'")
        ->call('edit')
        ->assertSet('expanded', true);

    $html = $component->html();

    expect(strpos($html, 'modalDeleteEmailMessageItem'.$email->id.'.showModal()'))->not->toBeFalse()
        ->toBeLessThan(strpos($html, 'wire:click="cancel"'));
});

it('follows an edit made to the same email elsewhere on the page', function () {
    $email = loggedEmail();
    $copy = Livewire::test(EmailMessageItem::class, ['emailmessage' => $email]);

    Livewire::test(EmailMessageItem::class, ['emailmessage' => $email])
        ->call('edit')
        ->set('subject', 'Quote v2')
        ->call('update')
        ->assertDispatched('email-message-updated.'.$email->id);

    $copy->dispatch('email-message-updated.'.$email->id)->assertSet('subject', 'Quote v2');
});
