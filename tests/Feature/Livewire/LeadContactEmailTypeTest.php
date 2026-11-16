<?php

/*
 * A person created from a lead got an email with no type (#144).
 *
 * The lead form's email-type select has no blank option, so it shows "Work",
 * but the Livewire property had no default and stayed null. The person was
 * saved with `type = null`, and the person form, which requires a type next to
 * an address, then refused every later edit with "The type field is required".
 *
 * The forms are mounted through render-stub subclasses so the real rules run
 * without the tables their blades reach for.
 */

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadCreate;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationEdit;
use VentureDrake\LaravelCrm\Livewire\People\PersonEdit;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\PersonService;

class EmailTypeLeadCreate extends LeadCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class EmailTypePersonEdit extends PersonEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class EmailTypeOrganizationEdit extends OrganizationEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    $pipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Lead Pipeline',
        'model' => Lead::class,
    ]);
    $pipeline->pipelineStages()->create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'First',
        'order' => 0,
    ]);
});

it('saves the type the lead form shows when it is left alone', function () {
    Livewire::test(EmailTypeLeadCreate::class)
        ->set('title', 'A lead')
        ->set('person_name', 'Pat Smith')
        ->set('email', 'pat@example.com')
        ->set('phone', '0412 345 678')
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::firstOrFail();

    expect($person->emails()->first()->type)->toBe('work')
        ->and($person->phones()->first()->type)->toBe('mobile');
});

it('keeps a type chosen on the lead form', function () {
    Livewire::test(EmailTypeLeadCreate::class)
        ->set('title', 'A lead')
        ->set('person_name', 'Pat Smith')
        ->set('email', 'pat@example.com')
        ->set('email_type', 'home')
        ->call('save')
        ->assertHasNoErrors();

    expect(Person::firstOrFail()->emails()->first()->type)->toBe('home');
});

it('falls back to a type when a caller sends none', function () {
    $person = app(PersonService::class)->createFromRelated((object) [
        'person_name' => 'Pat Smith',
        'user_owner_id' => auth()->id(),
        'email' => 'pat@example.com',
        'email_type' => null,
        'email_primary' => true,
        'phone' => '0412 345 678',
        'phone_type' => null,
        'phone_primary' => true,
    ]);

    expect($person->emails()->first()->type)->toBe('work')
        ->and($person->phones()->first()->type)->toBe('mobile');
});

describe('a contact already saved with no type', function () {
    dataset('records', [
        'person' => fn () => [EmailTypePersonEdit::class, 'person', Person::create(['first_name' => 'Pat'])],
        'organization' => fn () => [EmailTypeOrganizationEdit::class, 'organization', Organization::create(['name' => 'Acme'])],
    ]);

    it('shows the default type and allows an unrelated edit', function (array $form) {
        [$component, $param, $record] = $form;
        $record->emails()->create(['address' => 'pat@example.com', 'type' => null, 'primary' => 1]);
        $record->phones()->create(['number' => '0412 345 678', 'type' => null, 'primary' => 1]);

        Livewire::test($component, [$param => $record])
            ->assertSet('emails.0.type', 'work')
            ->assertSet('phones.0.type', 'mobile')
            ->set('description', 'Changed')
            ->call('save')
            ->assertHasNoErrors();

        $email = $record->emails()->first();
        $phone = $record->phones()->first();

        expect($record->fresh()->description)->toBe('Changed')
            ->and($email->address)->toBe('pat@example.com')
            ->and($email->type)->toBe('work')
            ->and($phone->number)->toBe('0412 345 678')
            ->and($phone->type)->toBe('mobile');
    })->with('records');

    it('still requires a type on a new email row', function () {
        Livewire::test(EmailTypePersonEdit::class, ['person' => Person::create(['first_name' => 'Pat'])])
            ->set('emails', [['id' => null, 'address' => 'pat@example.com', 'type' => null]])
            ->call('save')
            ->assertHasErrors(['emails.0.type']);
    });
});
