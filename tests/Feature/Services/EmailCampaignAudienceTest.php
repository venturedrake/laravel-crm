<?php

use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;
use VentureDrake\LaravelCrm\Support\EmailCampaignAudience;

function audiencePerson(string $first, ?string $address, array $attributes = [], bool $subscribed = true): Person
{
    $person = Person::create(['first_name' => $first, 'last_name' => 'Test', ...$attributes]);

    if ($address !== null) {
        $person->emails()->create(['address' => $address, 'primary' => true, 'subscribed' => $subscribed]);
    }

    return $person;
}

/**
 * @return list<string>
 */
function audienceAddresses(string $type, array $ids = []): array
{
    $addresses = [];

    EmailCampaignAudience::each($type, $ids, function (Email $email) use (&$addresses) {
        $addresses[] = $email->address;
    });

    sort($addresses);

    return $addresses;
}

function audienceCampaign(array $attributes = []): EmailCampaign
{
    return EmailCampaign::create([
        'name' => 'Audience campaign',
        'subject' => 'Subject',
        'body' => 'Body',
        'status' => 'draft',
        ...$attributes,
    ]);
}

it('targets every subscribed person for the all audience', function () {
    audiencePerson('Ann', 'ann@example.com');
    audiencePerson('Bob', 'bob@example.com');
    audiencePerson('Cat', 'cat@example.com', subscribed: false);

    expect(audienceAddresses(EmailCampaign::AUDIENCE_ALL))->toBe(['ann@example.com', 'bob@example.com'])
        ->and(EmailCampaignAudience::count(EmailCampaign::AUDIENCE_ALL, []))->toBe(2);
});

it('de-duplicates addresses case-insensitively', function () {
    audiencePerson('Ann', 'ann@example.com');
    audiencePerson('Ann2', ' ANN@example.com ');

    expect(EmailCampaignAudience::count(EmailCampaign::AUDIENCE_ALL, []))->toBe(1);
});

it('ignores organization emails', function () {
    $organization = Organization::create(['name' => 'Acme']);
    $organization->emails()->create(['address' => 'info@acme.test', 'subscribed' => true]);
    audiencePerson('Ann', 'ann@example.com');

    expect(audienceAddresses(EmailCampaign::AUDIENCE_ALL))->toBe(['ann@example.com']);
});

it('filters by label', function () {
    $vip = Label::create(['name' => 'VIP']);
    $other = Label::create(['name' => 'Other']);

    audiencePerson('Ann', 'ann@example.com')->labels()->attach($vip);
    audiencePerson('Bob', 'bob@example.com')->labels()->attach($other);
    audiencePerson('Cat', 'cat@example.com', subscribed: false)->labels()->attach($vip);

    expect(audienceAddresses(EmailCampaign::AUDIENCE_LABELS, [$vip->id]))->toBe(['ann@example.com'])
        ->and(audienceAddresses(EmailCampaign::AUDIENCE_LABELS, [$vip->id, $other->id]))->toBe(['ann@example.com', 'bob@example.com']);
});

it('filters by organization', function () {
    $acme = Organization::create(['name' => 'Acme']);
    $globex = Organization::create(['name' => 'Globex']);

    audiencePerson('Ann', 'ann@example.com', ['organization_id' => $acme->id]);
    audiencePerson('Bob', 'bob@example.com', ['organization_id' => $globex->id]);
    audiencePerson('Cat', 'cat@example.com');

    expect(audienceAddresses(EmailCampaign::AUDIENCE_ORGANIZATIONS, [$acme->id]))->toBe(['ann@example.com']);
});

it('filters by lead or deal pipeline stage', function () {
    $leadStage = Pipeline::create(['name' => 'Leads', 'model' => Lead::class])
        ->pipelineStages()->create(['name' => 'New']);
    $dealStage = Pipeline::create(['name' => 'Deals', 'model' => Deal::class])
        ->pipelineStages()->create(['name' => 'Won']);

    $ann = audiencePerson('Ann', 'ann@example.com');
    $bob = audiencePerson('Bob', 'bob@example.com');
    audiencePerson('Cat', 'cat@example.com');

    Lead::create(['title' => 'Lead', 'person_id' => $ann->id, 'pipeline_stage_id' => $leadStage->id]);
    Deal::create(['title' => 'Deal', 'person_id' => $bob->id, 'pipeline_stage_id' => $dealStage->id]);

    expect(audienceAddresses(EmailCampaign::AUDIENCE_PIPELINE_STAGES, [$leadStage->id]))->toBe(['ann@example.com'])
        ->and(audienceAddresses(EmailCampaign::AUDIENCE_PIPELINE_STAGES, [$dealStage->id]))->toBe(['bob@example.com'])
        ->and(audienceAddresses(EmailCampaign::AUDIENCE_PIPELINE_STAGES, [$leadStage->id, $dealStage->id]))->toBe(['ann@example.com', 'bob@example.com']);
});

it('filters by person', function () {
    $ann = audiencePerson('Ann', 'ann@example.com');
    audiencePerson('Bob', 'bob@example.com');
    $cat = audiencePerson('Cat', 'cat@example.com', subscribed: false);

    expect(audienceAddresses(EmailCampaign::AUDIENCE_PEOPLE, [$ann->id, $cat->id]))->toBe(['ann@example.com']);
});

it('matches nothing for a filtered type with no ids', function (string $type) {
    audiencePerson('Ann', 'ann@example.com');

    expect(EmailCampaignAudience::count($type, []))->toBe(0);
})->with([
    EmailCampaign::AUDIENCE_LABELS,
    EmailCampaign::AUDIENCE_ORGANIZATIONS,
    EmailCampaign::AUDIENCE_PIPELINE_STAGES,
    EmailCampaign::AUDIENCE_PEOPLE,
]);

it('matches nothing for an unknown audience type', function () {
    $ann = audiencePerson('Ann', 'ann@example.com');

    expect(EmailCampaignAudience::count('everyone', [$ann->id]))->toBe(0);
});

it('materialises recipients from the campaign audience', function () {
    $vip = Label::create(['name' => 'VIP']);
    $ann = audiencePerson('Ann', 'ann@example.com');
    $ann->labels()->attach($vip);
    audiencePerson('Bob', 'bob@example.com');

    $campaign = audienceCampaign([
        'audience_type' => EmailCampaign::AUDIENCE_LABELS,
        'audience_ids' => [$vip->id],
    ]);

    app(EmailCampaignService::class)->materialiseRecipients($campaign);

    expect($campaign->fresh()->total_recipients)->toBe(1)
        ->and($campaign->recipients()->pluck('person_id')->all())->toBe([$ann->id]);
});

it('still targets everyone for a legacy campaign with no audience', function () {
    audiencePerson('Ann', 'ann@example.com');
    audiencePerson('Bob', 'bob@example.com');

    // Rows that predate the audience columns get the column default and no ids.
    $campaign = audienceCampaign()->fresh();

    expect($campaign->audience_type)->toBe('all')
        ->and($campaign->audience_ids)->toBeNull();

    app(EmailCampaignService::class)->materialiseRecipients($campaign);

    expect($campaign->fresh()->total_recipients)->toBe(2);
});

it('stores no ids for an all audience and casts filtered ids to integers', function () {
    $this->actingAsUser();
    $service = app(EmailCampaignService::class);

    $all = $service->create([
        'name' => 'All', 'subject' => 'S', 'body' => 'B',
        'audience_type' => EmailCampaign::AUDIENCE_ALL, 'audience_ids' => [1, 2],
    ]);
    $filtered = $service->create([
        'name' => 'Filtered', 'subject' => 'S', 'body' => 'B',
        'audience_type' => EmailCampaign::AUDIENCE_PEOPLE, 'audience_ids' => ['3', '4'],
    ]);

    expect($all->fresh()->audience_type)->toBe('all')
        ->and($all->fresh()->audience_ids)->toBeNull()
        ->and($filtered->fresh()->audience_ids)->toBe([3, 4]);
});

it('describes the audience for the show page', function () {
    $vip = Label::create(['name' => 'VIP']);
    $news = Label::create(['name' => 'Newsletter']);

    $campaign = audienceCampaign([
        'audience_type' => EmailCampaign::AUDIENCE_LABELS,
        'audience_ids' => [$vip->id, $news->id],
    ]);

    expect(EmailCampaignAudience::describe($campaign))->toBe('Labels: Newsletter, VIP')
        ->and(EmailCampaignAudience::describe(audienceCampaign()))->toBe('All subscribed contacts');
});

it('reports whether an audience has anyone without counting it', function () {
    $vip = Label::create(['name' => 'VIP']);
    audiencePerson('Ann', 'ann@example.com');

    expect(EmailCampaignAudience::any(EmailCampaign::AUDIENCE_ALL, []))->toBeTrue()
        ->and(EmailCampaignAudience::any(EmailCampaign::AUDIENCE_LABELS, [$vip->id]))->toBeFalse()
        ->and(EmailCampaignAudience::any(EmailCampaign::AUDIENCE_LABELS, []))->toBeFalse();
});

it('de-duplicates and counts encrypted addresses', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);

    audiencePerson('Ann', 'ann@example.com');
    audiencePerson('Ann2', ' ANN@example.com ');
    audiencePerson('Bob', 'bob@example.com');

    expect(Email::query()->toBase()->pluck('address')->all())->not->toContain('bob@example.com')
        ->and(EmailCampaignAudience::count(EmailCampaign::AUDIENCE_ALL, []))->toBe(2)
        ->and(EmailCampaignAudience::any(EmailCampaign::AUDIENCE_ALL, []))->toBeTrue()
        ->and(audienceAddresses(EmailCampaign::AUDIENCE_ALL))->toBe(['ann@example.com', 'bob@example.com']);
});

it('does not add a recipient twice when materialised again', function () {
    $ann = audiencePerson('Ann', 'ann@example.com');
    $campaign = audienceCampaign();
    $service = app(EmailCampaignService::class);

    expect($service->materialiseRecipients($campaign))->toBe(1);

    audiencePerson('Bob', 'bob@example.com');

    expect($service->materialiseRecipients($campaign))->toBe(1)
        ->and($campaign->fresh()->total_recipients)->toBe(2)
        ->and($campaign->recipients()->where('person_id', $ann->id)->count())->toBe(1);
});
