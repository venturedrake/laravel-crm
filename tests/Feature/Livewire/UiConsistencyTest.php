<?php

/*
 * Issue #113 — three UI inconsistencies found in QA.
 *
 * 1. Every form includes <x-crm-custom-fields> twice: flat inside the main
 *    card and with :group="true" below it. Group mode used to render the
 *    ungrouped fields as well, so each one had two inputs bound to the same
 *    fields.{id}, and a required one showed its error twice.
 * 2. Creating a team redirected to the dashboard instead of the new team.
 *    Teams mode keeps the dashboard: the user is switched into the new team,
 *    from where its show page would 404 or 403.
 * 3. Edit forms disagreed on where they went after saving. They now all go
 *    to the record's show page.
 */

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationEdit;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteCreate;
use VentureDrake\LaravelCrm\Livewire\Teams\TeamCreate;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\Team;

// Both calls exactly as the entity forms make them.
class UiConsistencyQuoteCreate extends QuoteCreate
{
    public function render()
    {
        return <<<'BLADE'
            <div>
                <x-crm-custom-fields :model="new \VentureDrake\LaravelCrm\Models\Quote()" />
                <x-crm-custom-fields :model="new \VentureDrake\LaravelCrm\Models\Quote()" :group="true" />
            </div>
            BLADE;
    }
}

class UiConsistencyTeamCreate extends TeamCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class UiConsistencyOrganizationEdit extends OrganizationEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

function uiConsistencyQuoteField(string $name, ?FieldGroup $group = null, bool $required = false): Field
{
    $field = Field::create([
        'name' => $name,
        'type' => 'text',
        'field_group_id' => $group?->id,
        'required' => $required,
    ]);

    FieldModel::create(['field_id' => $field->id, 'model' => Quote::class]);

    return $field;
}

beforeEach(function () {
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    app('laravel-crm.settings')->forgetCache();

    Pipeline::create(['name' => 'Quote Pipeline', 'model' => Quote::class])
        ->pipelineStages()->create(['name' => 'Draft', 'order' => 0]);
});

it('renders each custom field once across the flat and grouped component calls', function () {
    $this->actingAsUserWithPermissions(['create crm quotes']);

    $ungrouped = uiConsistencyQuoteField('Ungrouped field');
    $grouped = uiConsistencyQuoteField('Grouped field', FieldGroup::create(['name' => 'Extra details']));

    $html = Livewire::test(UiConsistencyQuoteCreate::class)->html();

    expect(substr_count($html, 'wire:model="fields.'.$ungrouped->id.'"'))->toBe(1)
        ->and(substr_count($html, 'wire:model="fields.'.$grouped->id.'"'))->toBe(1)
        ->and(substr_count($html, 'Extra details'))->toBe(1);
});

it('shows a required ungrouped custom field error once on the quote form', function () {
    $this->actingAsUserWithPermissions(['create crm quotes']);

    $field = uiConsistencyQuoteField('Purchase reference', required: true);

    $component = Livewire::test(UiConsistencyQuoteCreate::class)
        ->call('save')
        ->assertHasErrors(['fields.'.$field->id => 'required']);

    expect(substr_count($component->html(), 'The Purchase reference field is required.'))->toBe(1);
});

it('sends the user to the new team after creating it', function () {
    $this->actingAsUserWithPermissions(['view crm teams', 'create crm teams']);

    $component = Livewire::test(UiConsistencyTeamCreate::class)
        ->set('name', 'Redirect team')
        ->call('save');

    $team = Team::where('name', 'Redirect team')->firstOrFail();

    $component->assertRedirect(route('laravel-crm.teams.show', $team));
});

it('sends the user to the dashboard after creating a team in teams mode', function () {
    // Creating a team switches the user into it, and the new team's show page
    // is not reachable from there — see TeamCreate::save().
    config(['laravel-crm.teams' => true]);
    $this->actingAsUserWithPermissions(['view crm teams', 'create crm teams']);

    Livewire::test(UiConsistencyTeamCreate::class)
        ->set('name', 'Teams mode team')
        ->call('save')
        ->assertRedirect(route('laravel-crm.dashboard'));
});

it('sends the user to the show page after editing an organization', function () {
    $this->actingAsUserWithPermissions(['view crm organizations', 'edit crm organizations']);
    $organization = Organization::create(['name' => 'Redirect Org']);

    Livewire::test(UiConsistencyOrganizationEdit::class, ['organization' => $organization])
        ->call('save')
        ->assertRedirect(route('laravel-crm.organizations.show', $organization));
});

it('redirects every edit form to the show page after a successful save', function () {
    // Feature statuses have no show page.
    $exceptions = ['Settings/Features/FeatureStatusEdit.php'];

    $root = realpath(__DIR__.'/../../../src/Livewire');
    $offenders = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), 'Edit.php')) {
            continue;
        }

        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));

        if (in_array($relative, $exceptions, true)) {
            continue;
        }

        if (preg_match("/redirectTo:\s*route\('laravel-crm\.[a-z.-]+\.index'/", file_get_contents($file->getPathname()))) {
            $offenders[] = $relative;
        }
    }

    expect($offenders)->toBe([]);
});
