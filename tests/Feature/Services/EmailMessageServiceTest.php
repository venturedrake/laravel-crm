<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\EmailMessage;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\EmailMessageService;

/*
 * A logged email is recorded against a record, like a call or a note, and
 * shows on that record's timeline.
 */

beforeEach(function () {
    $this->user = $this->actingAsUser(['crm_access' => 1, 'email' => 'me@crm.test']);
    $this->service = app(EmailMessageService::class);
    $this->person = Person::create(['first_name' => 'Sam', 'last_name' => 'Lee']);
});

function logEmail(array $data = [], array $people = [], $record = null): EmailMessage
{
    return app(EmailMessageService::class)->create($record ?? test()->person, array_merge([
        'direction' => 'outbound',
        'sent_at' => '2026-11-10T10:15',
        'from_address' => 'me@crm.test',
        'to' => 'sam@example.org',
        'subject' => 'Quote',
        'body_html' => '<p>Hi <b>Sam</b></p><script>alert(1)</script>',
    ], $data), $people);
}

it('logs an email against a record with a cleaned body and a timeline entry', function () {
    $email = logEmail();

    expect($email->emailable->is($this->person))->toBeTrue()
        ->and($email->direction)->toBe('outbound')
        ->and($email->source)->toBe('manual')
        ->and($email->sent_at->format('Y-m-d H:i'))->toBe('2026-11-10 10:15')
        ->and($email->body_html)->toBe('<p>Hi <b>Sam</b></p>')
        ->and($email->body_text)->toBe('Hi Sam')
        ->and((int) $email->user_owner_id)->toBe($this->user->id)
        ->and($email->external_id)->not->toBeEmpty()
        ->and($this->person->emailMessages()->count())->toBe(1);

    $activity = $this->person->activities()->first();

    expect($activity->recordable->is($email))->toBeTrue()
        ->and((int) $activity->causeable_id)->toBe($this->user->id);
});

it('words the timeline entry by direction and dates it when the email was sent', function () {
    $sent = logEmail();
    $received = logEmail(['direction' => 'inbound']);

    expect($sent->timelineTitle('Mat'))->toBe('Mat logged a sent email')
        ->and($received->timelineTitle('Mat'))->toBe('Mat logged a received email')
        ->and($sent->timelineDate()->format('Y-m-d H:i'))->toBe('2026-11-10 10:15');
});

it('links other people involved and shows the email on their timelines', function () {
    $pat = Person::create(['first_name' => 'Pat']);
    $kim = Person::create(['first_name' => 'Kim']);

    $email = logEmail([], [$pat->id, $kim->id, $this->person->id]);

    // The person the email is logged against is not also an "other person".
    expect($email->contacts()->pluck('entityable_id')->sort()->values()->all())->toBe([$pat->id, $kim->id])
        ->and($pat->activities()->count())->toBe(1)
        ->and($kim->activities()->count())->toBe(1)
        ->and($this->person->activities()->count())->toBe(1);

    $this->service->update($email, ['subject' => 'Quote v2'], [$pat->id]);

    expect($email->fresh()->subject)->toBe('Quote v2')
        ->and($email->contacts()->pluck('entityable_id')->all())->toBe([$pat->id])
        ->and($kim->activities()->count())->toBe(0)
        ->and($pat->activities()->count())->toBe(1)
        ->and($this->person->activities()->count())->toBe(1);
});

it('leaves the people involved alone when an update does not send them', function () {
    $pat = Person::create(['first_name' => 'Pat']);
    $email = logEmail([], [$pat->id]);

    $this->service->update($email, ['subject' => 'Changed']);

    expect($email->contacts()->count())->toBe(1);
});

it('cleans the body again on update', function () {
    $email = logEmail();

    $this->service->update($email, ['body_html' => '<p>New</p><img src="https://x.example/p.gif">']);

    expect($email->fresh()->body_html)->toBe('<p>New</p>')
        ->and($email->fresh()->body_text)->toBe('New');
});

it('soft deletes an email and takes it off every timeline', function () {
    $pat = Person::create(['first_name' => 'Pat']);
    $email = logEmail([], [$pat->id]);

    $this->service->delete($email);

    expect(EmailMessage::find($email->id))->toBeNull()
        ->and(EmailMessage::withTrashed()->find($email->id))->not->toBeNull()
        ->and(Activity::where('recordable_type', $email->getMorphClass())->count())->toBe(0);
});

it('works out the direction from the sender', function () {
    expect($this->service->directionFor('ME@crm.test'))->toBe('outbound')
        ->and($this->service->directionFor('Sam <sam@example.org>'))->toBe('inbound')
        ->and($this->service->directionFor(null))->toBe('inbound');
});

it('suggests the record\'s primary email address, or its person\'s', function () {
    Email::create(['address' => 'other@example.org', 'primary' => false, 'emailable_type' => $this->person->getMorphClass(), 'emailable_id' => $this->person->id]);
    Email::create(['address' => 'sam@example.org', 'primary' => true, 'emailable_type' => $this->person->getMorphClass(), 'emailable_id' => $this->person->id]);

    $organization = Organization::create(['name' => 'No Address Co']);

    expect($this->service->contactEmailFor($this->person))->toBe('sam@example.org')
        ->and($this->service->contactEmailFor($organization))->toBeNull()
        ->and($this->service->contactEmailFor(null))->toBeNull();
});

it('stores addresses, subject and body encrypted when field encryption is on', function () {
    config()->set('laravel-crm.encrypt_db_fields', true);

    $email = logEmail(['from_name' => 'Me', 'cc' => 'cc@example.org']);

    $raw = DB::table('crm_email_messages')->where('id', $email->id)->first();

    foreach (['from_address', 'from_name', 'to', 'cc', 'subject', 'body_html', 'body_text'] as $field) {
        expect($raw->{$field})->not->toBe($email->fresh()->{$field})
            ->and(decrypt($raw->{$field}))->toBe($email->fresh()->{$field});
    }

    expect($email->fresh()->subject)->toBe('Quote')
        ->and($email->fresh()->body_html)->toBe('<p>Hi <b>Sam</b></p>');
});

it('creates and drops the email messages table from the loaded migration', function () {
    $files = glob(dirname(__DIR__, 3).'/database/updates/*_create_laravel_crm_email_messages_table.php');

    expect($files)->toHaveCount(1);

    // The test schema builds its own copy of the table; replace it with the
    // one the real migration creates.
    Schema::drop('crm_email_messages');
    $migration = require $files[0];
    $migration->up();

    expect(Schema::hasColumns('crm_email_messages', [
        'external_id', 'team_id', 'emailable_type', 'emailable_id', 'direction', 'source', 'from_address', 'from_name',
        'to', 'cc', 'subject', 'body_html', 'body_text', 'attachment_names', 'sent_at', 'user_owner_id',
        'user_created_id', 'user_updated_id', 'user_deleted_id', 'user_restored_id', 'deleted_at',
    ]))->toBeTrue();

    // Re-running is a no-op, as laravelcrm:update expects.
    $migration->up();

    $migration->down();
    expect(Schema::hasTable('crm_email_messages'))->toBeFalse();
});
