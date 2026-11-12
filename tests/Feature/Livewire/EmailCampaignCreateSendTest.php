<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\EmailCampaigns\EmailCampaignCreate;
use VentureDrake\LaravelCrm\Livewire\EmailCampaigns\EmailCampaignEdit;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

/**
 * Render-stub subclasses, as in EmailCampaignAuthorizationTest. Only render() is
 * replaced; save() runs for real.
 */
class SendEmailCampaignCreate extends EmailCampaignCreate
{
    public function render()
    {
        return '<div></div>';
    }
}
class SendEmailCampaignEdit extends EmailCampaignEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

function sendSubscribedPerson(string $first, string $address): Person
{
    $person = Person::create(['first_name' => $first, 'last_name' => 'Test']);
    $person->emails()->create(['address' => $address, 'primary' => true, 'subscribed' => true]);

    return $person;
}

function sendCreateForm()
{
    return Livewire::test(SendEmailCampaignCreate::class)
        ->set('name', 'Spring sale')
        ->set('subject', 'Spring sale')
        ->set('body', '<p>Hello</p>');
}

beforeEach(function () {
    $this->actingAsUserWithPermissions([
        'view crm email-campaigns', 'create crm email-campaigns', 'edit crm email-campaigns',
    ]);
});

it('saves a new campaign as a draft by default', function () {
    sendSubscribedPerson('Ann', 'ann@example.com');

    sendCreateForm()
        ->assertSet('send_mode', 'draft')
        ->assertSet('recipientCount', null)
        ->call('loadRecipientCount')
        ->assertSet('recipientCount', 1)
        ->call('save')
        ->assertHasNoErrors();

    $campaign = EmailCampaign::sole();

    expect($campaign->status)->toBe('draft')
        ->and($campaign->audience_type)->toBe('all')
        ->and($campaign->total_recipients)->toBe(0)
        ->and($campaign->recipients()->count())->toBe(0);
});

it('asks for confirmation before sending now and creates nothing until confirmed', function () {
    sendSubscribedPerson('Ann', 'ann@example.com');
    sendSubscribedPerson('Bob', 'bob@example.com');

    $component = sendCreateForm()
        ->set('send_mode', 'now')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('confirmingSend', true)
        ->assertSet('recipientCount', 2);

    expect(EmailCampaign::count())->toBe(0);

    $component->call('save', true)->assertHasNoErrors();

    $campaign = EmailCampaign::sole();

    expect($campaign->status)->toBe('scheduled')
        ->and($campaign->total_recipients)->toBe(2);
});

it('schedules without a confirmation step', function () {
    sendSubscribedPerson('Ann', 'ann@example.com');

    sendCreateForm()
        ->set('send_mode', 'schedule')
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('confirmingSend', false);

    expect(EmailCampaign::sole()->status)->toBe('scheduled');
});

it('recalculates the recipient count when the audience changes', function () {
    $vip = Label::create(['name' => 'VIP']);
    sendSubscribedPerson('Ann', 'ann@example.com')->labels()->attach($vip);
    sendSubscribedPerson('Bob', 'bob@example.com');

    sendCreateForm()
        ->call('loadRecipientCount')
        ->assertSet('recipientCount', 2)
        ->set('audience_type', EmailCampaign::AUDIENCE_LABELS)
        ->assertSet('audience_ids', [])
        ->assertSet('recipientCount', 0)
        ->set('audience_ids', [(string) $vip->id])
        ->assertSet('recipientCount', 1);
});

it('requires ids for a filtered audience', function () {
    sendCreateForm()
        ->set('audience_type', EmailCampaign::AUDIENCE_LABELS)
        ->call('save')
        ->assertHasErrors(['audience_ids']);

    expect(EmailCampaign::count())->toBe(0);
});

it('refuses to send to an audience with no recipients', function (string $mode) {
    $vip = Label::create(['name' => 'VIP']);
    sendSubscribedPerson('Ann', 'ann@example.com');

    sendCreateForm()
        ->set('audience_type', EmailCampaign::AUDIENCE_LABELS)
        ->set('audience_ids', [$vip->id])
        ->set('send_mode', $mode)
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save', true)
        ->assertHasErrors(['audience_ids'])
        ->assertSet('confirmingSend', false);

    expect(EmailCampaign::count())->toBe(0);
})->with(['now', 'schedule']);

it('sends only to the chosen audience', function () {
    $vip = Label::create(['name' => 'VIP']);
    $ann = sendSubscribedPerson('Ann', 'ann@example.com');
    $ann->labels()->attach($vip);
    sendSubscribedPerson('Bob', 'bob@example.com');

    sendCreateForm()
        ->set('audience_type', EmailCampaign::AUDIENCE_LABELS)
        ->set('audience_ids', [$vip->id])
        ->set('send_mode', 'now')
        ->call('save', true)
        ->assertHasNoErrors();

    $campaign = EmailCampaign::sole();

    expect($campaign->audience_ids)->toBe([$vip->id])
        ->and($campaign->recipients()->pluck('person_id')->all())->toBe([$ann->id]);
});

it('loads the saved audience on edit and keeps a draft a draft', function () {
    $vip = Label::create(['name' => 'VIP']);
    sendSubscribedPerson('Ann', 'ann@example.com')->labels()->attach($vip);

    $campaign = EmailCampaign::create([
        'name' => 'Draft', 'subject' => 'S', 'body' => 'B', 'status' => 'draft',
        'audience_type' => EmailCampaign::AUDIENCE_LABELS, 'audience_ids' => [$vip->id],
    ]);

    Livewire::test(SendEmailCampaignEdit::class, ['campaign' => $campaign])
        ->assertSet('audience_type', EmailCampaign::AUDIENCE_LABELS)
        ->assertSet('audience_ids', [$vip->id])
        ->assertSet('audienceOptions', [['id' => $vip->id, 'name' => 'VIP']])
        ->assertSet('recipientCount', null)
        ->call('loadRecipientCount')
        ->assertSet('recipientCount', 1)
        ->assertSet('send_mode', 'draft')
        ->set('name', 'Renamed draft')
        ->call('save')
        ->assertHasNoErrors();

    expect($campaign->fresh()->status)->toBe('draft')
        ->and($campaign->fresh()->name)->toBe('Renamed draft');
});

it('schedules an existing draft from the edit page', function () {
    $ann = sendSubscribedPerson('Ann', 'ann@example.com');
    sendSubscribedPerson('Bob', 'bob@example.com');

    $campaign = EmailCampaign::create(['name' => 'Draft', 'subject' => 'S', 'body' => 'B', 'status' => 'draft']);

    Livewire::test(SendEmailCampaignEdit::class, ['campaign' => $campaign])
        ->set('audience_type', EmailCampaign::AUDIENCE_PEOPLE)
        ->set('audience_ids', [$ann->id])
        ->set('send_mode', 'schedule')
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $campaign->refresh();

    expect($campaign->status)->toBe('scheduled')
        ->and($campaign->audience_type)->toBe(EmailCampaign::AUDIENCE_PEOPLE)
        ->and($campaign->total_recipients)->toBe(1);
});

it('renders the real create and edit views with the audience controls', function () {
    $vip = Label::create(['name' => 'VIP']);

    Livewire::test(EmailCampaignCreate::class)
        ->assertSee(ucfirst(__('laravel-crm::lang.audience')))
        ->assertSee(ucfirst(__('laravel-crm::lang.save_as_draft')))
        ->set('audience_type', EmailCampaign::AUDIENCE_LABELS)
        ->assertSee('VIP');

    $campaign = EmailCampaign::create(['name' => 'Draft', 'subject' => 'S', 'body' => 'B', 'status' => 'draft']);

    Livewire::test(EmailCampaignEdit::class, ['campaign' => $campaign])
        ->assertSee(ucfirst(__('laravel-crm::lang.audience')));
});

it('loads only the selected people and finds the rest by search', function () {
    $ann = sendSubscribedPerson('Ann', 'ann@example.com');
    $bob = sendSubscribedPerson('Bob', 'bob@example.com');
    sendSubscribedPerson('Cat', 'cat@example.com');

    $component = sendCreateForm()
        ->set('audience_type', EmailCampaign::AUDIENCE_PEOPLE)
        ->assertSet('audienceOptions', [])
        ->call('searchAudience', 'bob')
        ->assertSet('audienceOptions', [['id' => $bob->id, 'name' => 'Bob Test']])
        ->set('audience_ids', [$bob->id])
        ->assertSet('recipientCount', 1)
        ->call('searchAudience', 'ann');

    expect(collect($component->get('audienceOptions'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$ann->id, $bob->id])->sort()->values()->all());
});

it('searches organizations by name, encrypted or not', function (bool $encrypted) {
    config(['laravel-crm.encrypt_db_fields' => $encrypted]);

    $acme = Organization::create(['name' => 'Acme Ltd']);
    Organization::create(['name' => 'Globex']);

    sendCreateForm()
        ->set('audience_type', EmailCampaign::AUDIENCE_ORGANIZATIONS)
        ->call('searchAudience', 'acme')
        ->assertSet('audienceOptions', [['id' => $acme->id, 'name' => 'Acme Ltd']]);
})->with([false, true]);

it('loads short lists such as labels in full', function () {
    $vip = Label::create(['name' => 'VIP']);
    $news = Label::create(['name' => 'Newsletter']);

    sendCreateForm()
        ->set('audience_type', EmailCampaign::AUDIENCE_LABELS)
        ->assertSet('audienceOptions', [
            ['id' => $news->id, 'name' => 'Newsletter'],
            ['id' => $vip->id, 'name' => 'VIP'],
        ]);
});

it('refuses audience reads to a user who cannot create campaigns', function (string $method) {
    $component = sendCreateForm();

    $this->actingAsUserWithPermissions(['view crm email-campaigns']);

    $component->call($method)->assertForbidden();
})->with(['loadRecipientCount', 'searchAudience']);
