<?php

/*
 * A lead converts to a deal once.
 *
 * LD-5006 was converted but its show page still offered Convert, and nothing
 * below the button checked, so it could be converted again into a second deal.
 * The list and board hide converted leads, which left the show page, a stale
 * form and the API. The show page now says so and links the deal, DealCreate
 * turns a converted lead away, and DealService refuses it for every caller.
 * Conversion also moves the lead to its pipeline's won stage.
 */

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Deals\DealCreate;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadShow;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function leadPipeline(array $stages): Pipeline
{
    $pipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Lead Pipeline',
        'model' => Lead::class,
    ]);

    foreach ($stages as $order => $name) {
        $pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => $name,
            'order' => $order,
        ]);
    }

    return $pipeline;
}

function newLead(array $attributes = []): Lead
{
    return Lead::create([
        'title' => 'Quinn Tester Lead',
        'amount' => 100,
        'currency' => 'USD',
        'person_id' => Person::create(['first_name' => 'Quinn', 'last_name' => 'Tester'])->id,
        ...$attributes,
    ]);
}

function convertLead(Lead $lead)
{
    return Livewire::test(DealCreate::class, ['fromModelType' => 'lead', 'fromModelId' => $lead->id])
        ->set('title', 'Quinn Tester Lead')
        ->set('amount', 100)
        ->call('save');
}

beforeEach(function () {
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    $dealPipeline = Pipeline::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Deal Pipeline',
        'model' => Deal::class,
    ]);
    $dealPipeline->pipelineStages()->create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Pending',
        'order' => 0,
    ]);

    $this->actingAsUserWithPermissions([
        'view crm leads', 'edit crm leads', 'view crm deals', 'create crm deals',
    ]);
});

describe('the lead show page', function () {
    it('offers Convert on a lead that has not been converted', function () {
        Livewire::test(LeadShow::class, ['lead' => newLead()])
            ->assertSee(ucfirst(__('laravel-crm::lang.convert')))
            ->assertDontSee(ucfirst(__('laravel-crm::lang.converted')));
    });

    it('shows a dated badge and the deal instead of Convert once converted', function () {
        $lead = newLead(['converted_at' => '2026-11-01 11:49:11']);
        $deal = Deal::create(['title' => 'From the lead', 'lead_id' => $lead->id, 'currency' => 'USD']);

        Livewire::test(LeadShow::class, ['lead' => $lead])
            ->assertDontSee('model=lead', false)
            ->assertDontSee(route('laravel-crm.deals.create', ['model' => 'lead', 'id' => $lead->id]), false)
            ->assertSee(ucfirst(__('laravel-crm::lang.converted')).' 2026-11-01')
            ->assertSee(route('laravel-crm.deals.show', $deal), false)
            ->assertSee($deal->deal_id);
    });

    it('shows the badge alone when the lead has no deal to link', function () {
        $lead = newLead(['converted_at' => now()]);

        Livewire::test(LeadShow::class, ['lead' => $lead])
            ->assertDontSee('model=lead', false)
            ->assertSee(ucfirst(__('laravel-crm::lang.converted')));
    });

    it('does not link a deal the user cannot view', function () {
        $lead = newLead(['converted_at' => now()]);
        $deal = Deal::create(['title' => 'From the lead', 'lead_id' => $lead->id, 'currency' => 'USD']);

        $this->actingAsUserWithPermissions(['view crm leads']);

        Livewire::test(LeadShow::class, ['lead' => $lead])
            ->assertDontSee(route('laravel-crm.deals.show', $deal), false);
    });
});

describe('opening deal create', function () {
    it('turns a converted lead back to the lead with a message', function () {
        $lead = newLead(['converted_at' => now()]);

        Livewire::test(DealCreate::class, ['fromModelType' => 'lead', 'fromModelId' => $lead->id])
            ->assertRedirect(route('laravel-crm.leads.show', $lead));
    });

    it('turns the page itself away too', function () {
        $lead = newLead(['converted_at' => now()]);

        $this->get(route('laravel-crm.deals.create', ['model' => 'lead', 'id' => $lead->id]))
            ->assertRedirect(route('laravel-crm.leads.show', $lead));

        $flashed = collect(session('flasher::envelopes', []))
            ->map(fn ($envelope) => (is_string($envelope) ? unserialize($envelope) : $envelope)->getMessage());

        expect($flashed)->toContain('This lead has already been converted to a deal');
    });

    it('still opens from a lead that has not been converted', function () {
        $lead = newLead();

        Livewire::test(DealCreate::class, ['fromModelType' => 'lead', 'fromModelId' => $lead->id])
            ->assertNoRedirect()
            ->assertSet('lead_id', $lead->id)
            ->assertSet('title', 'Quinn Tester Lead');
    });
});

describe('saving a deal', function () {
    it('converts a lead, links the deal and moves the lead to the won stage', function (string $stage) {
        $pipeline = leadPipeline(['New', $stage, 'Closed Lost']);
        $new = $pipeline->pipelineStages()->where('name', 'New')->first();
        $lead = newLead(['pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $new->id]);

        convertLead($lead)->assertHasNoErrors();

        expect(Deal::where('lead_id', $lead->id)->count())->toBe(1)
            ->and($lead->fresh()->converted_at)->not->toBeNull()
            ->and($lead->fresh()->pipelineStage->name)->toBe($stage)
            ->and($lead->fresh()->deal->lead_id)->toBe($lead->id)
            ->and($lead->fresh()->deal->dealProducts()->withTrashed()->count())->toBe(0);
    })->with(['Closed Won', 'Won', 'Converted']);

    it('leaves the stage alone when the pipeline has no won stage', function () {
        $pipeline = leadPipeline(['New', 'Qualified']);
        $new = $pipeline->pipelineStages()->where('name', 'New')->first();
        $lead = newLead(['pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $new->id]);

        convertLead($lead)->assertHasNoErrors();

        expect($lead->fresh()->converted_at)->not->toBeNull()
            ->and($lead->fresh()->pipeline_stage_id)->toBe($new->id);
    });

    it('refuses when the lead was converted after the form was opened', function () {
        $lead = newLead();

        $form = Livewire::test(DealCreate::class, ['fromModelType' => 'lead', 'fromModelId' => $lead->id])
            ->set('title', 'Second deal')
            ->set('amount', 100)
            ->set('person_name', 'New Person');

        $lead->update(['converted_at' => now()]);
        $people = Person::count();

        $form->call('save')->assertRedirect(route('laravel-crm.leads.show', $lead));

        expect(Deal::count())->toBe(0)
            ->and(Person::count())->toBe($people);
    });

    it('will not let the browser swap the lead out', function () {
        $form = Livewire::test(DealCreate::class, ['fromModelType' => 'lead', 'fromModelId' => newLead()->id]);

        $form->set('lead_id', null);
    })->throws(CannotUpdateLockedPropertyException::class);

    it('refuses in the service, so every caller is held to it', function () {
        $lead = newLead(['converted_at' => now()]);

        try {
            app(DealService::class)->create((object) [
                'lead_id' => $lead->id,
                'title' => 'Second deal',
                'description' => null,
                'amount' => 100,
                'currency' => 'USD',
                'expected_close' => null,
                'user_owner_id' => null,
                'pipeline_stage_id' => null,
            ]);

            $this->fail('The service created a second deal.');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('lead_id');
        }

        expect(Deal::count())->toBe(0);
    });

    it('still creates a deal with no lead', function () {
        $person = Person::create(['first_name' => 'Quinn', 'last_name' => 'Tester']);

        Livewire::test(DealCreate::class)
            ->set('person_id', $person->id)
            ->set('person_name', $person->name)
            ->set('title', 'Standalone')
            ->set('amount', 100)
            ->call('save')
            ->assertHasNoErrors();

        expect(Deal::count())->toBe(1);
    });
});

describe('deleting the deal a lead became', function () {
    it('lets the lead convert again', function () {
        $lead = newLead();
        convertLead($lead);

        Deal::firstOrFail()->delete();

        expect($lead->fresh()->isConverted())->toBeFalse();

        Livewire::test(LeadShow::class, ['lead' => $lead->fresh()])
            ->assertSee('model=lead', false);

        convertLead($lead->fresh())->assertHasNoErrors();

        expect($lead->fresh()->isConverted())->toBeTrue()
            ->and($lead->fresh()->deal->id)->toBe(Deal::firstOrFail()->id);
    });

    it('keeps the lead converted while it has another deal', function () {
        $lead = newLead(['converted_at' => now()]);
        $deal = Deal::create(['title' => 'First', 'lead_id' => $lead->id, 'currency' => 'USD']);
        Deal::create(['title' => 'Second', 'lead_id' => $lead->id, 'currency' => 'USD']);

        $deal->delete();

        expect($lead->fresh()->isConverted())->toBeTrue();
    });

    it('leaves a deal with no lead to delete as before', function () {
        $deal = Deal::create(['title' => 'Standalone', 'currency' => 'USD']);

        $deal->delete();

        expect(Deal::count())->toBe(0);
    });
});

it('refuses a converted lead through the API', function () {
    $user = User::create([
        'name' => 'Api', 'email' => 'lead-api@example.com', 'password' => bcrypt('secret'), 'crm_access' => true,
    ]);
    Gate::before(fn () => true);
    $lead = newLead(['converted_at' => now()]);

    $this->withHeaders(['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken, 'Accept' => 'application/json'])
        ->postJson('/crm/api/v2/deals', ['title' => 'Second deal', 'lead_id' => $lead->external_id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['lead_id']);

    expect(Deal::count())->toBe(0);
});
