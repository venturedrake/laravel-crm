<?php

use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadCreate;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadEdit;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;

// Keep the real mount hooks and custom field controls without the unrelated lead form.
class CustomFieldDefaultsLeadCreate extends LeadCreate
{
    public function render()
    {
        return '<div><x-crm-custom-fields :model="new \VentureDrake\LaravelCrm\Models\Lead" /></div>';
    }
}

class CustomFieldDefaultsLeadEdit extends LeadEdit
{
    public function render()
    {
        return '<div><x-crm-custom-fields :model="$lead" /></div>';
    }
}

function makeLeadDefaultField(string $type): Field
{
    $field = Field::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'Custom '.$type,
        'key' => 'custom_'.$type,
        'type' => $type,
    ]);

    FieldModel::create([
        'external_id' => Str::uuid()->toString(),
        'field_id' => $field->id,
        'model' => Lead::class,
    ]);

    return $field;
}

it('includes every custom field binding in the initial lead state and subsequent responses', function () {
    $this->actingAsUser();
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    $expected = [];

    foreach (['text', 'textarea', 'date', 'select', 'radio', 'checkbox', 'checkbox_multiple'] as $type) {
        $field = makeLeadDefaultField($type);
        $expected[$field->id] = match ($type) {
            'checkbox' => false,
            'checkbox_multiple' => [],
            default => null,
        };
    }

    // Comparing the whole array distinguishes a missing key from an explicit null:
    // only the former causes Livewire's entangle initialization to fail.
    Livewire::test(CustomFieldDefaultsLeadCreate::class)
        ->assertSet('fields', $expected)
        ->call('$refresh')
        ->assertSet('fields', $expected);
});

it('preserves a saved date when the custom field defaults mount', function (?string $value) {
    $this->actingAsUserWithPermissions(['view crm leads', 'edit crm leads']);
    $field = makeLeadDefaultField('date');
    $field->update(['default' => '2026-12-01']);
    $lead = Lead::create(['title' => 'Custom date', 'currency' => 'USD']);
    $lead->fields()->updateOrCreate(['field_id' => $field->id], ['value' => $value]);

    Livewire::test(CustomFieldDefaultsLeadEdit::class, ['lead' => $lead])
        ->assertSet('fields', [$field->id => $value])
        ->call('$refresh')
        ->assertSet('fields', [$field->id => $value]);
})->with([null, '2026-11-20']);

it('preserves configured defaults when saving untouched custom fields', function (string $type, string $default) {
    $this->actingAsUser();
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    $field = makeLeadDefaultField($type);
    $expected = $default;

    if (in_array($type, ['select', 'radio', 'checkbox_multiple'], true)) {
        $option = $field->fieldOptions()->create([
            'external_id' => Str::uuid()->toString(),
            'value' => 'website',
            'label' => 'Website',
            'order' => 1,
        ]);
        $expected = $type === 'checkbox_multiple' ? [(string) $option->id] : (string) $option->id;
        if ($default === 'option-id') {
            $default = (string) $option->id;
        }
    } elseif ($type === 'checkbox') {
        $expected = (bool) $default;
    }

    $field->update(['default' => $default]);
    $person = Person::create(['first_name' => 'Custom', 'last_name' => 'Defaults']);

    Livewire::test(CustomFieldDefaultsLeadCreate::class)
        ->assertSet('fields', [$field->id => $expected])
        ->call('$refresh')
        ->assertSet('fields', [$field->id => $expected])
        ->set('person_id', $person->id)
        ->set('person_name', $person->name)
        ->set('title', 'Untouched defaults')
        ->call('save')
        ->assertHasNoErrors();

    $stored = is_array($expected) ? json_encode($expected) : (is_bool($expected) ? ($expected ? '1' : '0') : $expected);
    expect(Lead::firstOrFail()->fields()->where('field_id', $field->id)->firstOrFail()->value)->toBe($stored);
})->with([
    ['text', 'Website'],
    ['textarea', 'Website enquiry'],
    ['date', '2026-11-20'],
    ['checkbox', '1'],
    ['checkbox', '0'],
    ['select', 'website'],
    ['select', 'option-id'],
    ['radio', 'website'],
    ['checkbox_multiple', '["website"]'],
]);

it('allows clearing a configured default before saving', function () {
    $this->actingAsUser();
    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    $field = makeLeadDefaultField('text');
    $field->update(['default' => 'Website']);
    $person = Person::create(['first_name' => 'Custom', 'last_name' => 'Defaults']);

    Livewire::test(CustomFieldDefaultsLeadCreate::class)
        ->set('person_id', $person->id)
        ->set('person_name', $person->name)
        ->set('title', 'Cleared default')
        ->set('fields.'.$field->id, null)
        ->call('save')
        ->assertHasNoErrors();

    expect(Lead::firstOrFail()->fields()->where('field_id', $field->id)->firstOrFail()->value)->toBeNull();
});
