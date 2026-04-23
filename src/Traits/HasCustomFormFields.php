<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Validation\Rule;
use VentureDrake\LaravelCrm\Models\FieldModel;

/**
 * Adds support for the custom field UI rendered by <x-crm-custom-fields />.
 *
 * Components using this trait expose a `public array $fields` property bound
 * to `wire:model="fields.{fieldId}"` and automatically gain validation rules
 * derived from the configured custom fields for the related model.
 *
 * Consumers must implement `customFieldsModel()` to return the FQCN of the
 * model whose custom fields should be loaded (e.g. `Lead::class`).
 */
trait HasCustomFormFields
{
    /**
     * Custom field values keyed by field id.
     */
    public array $fields = [];

    /**
     * Cached collection of Field models for this component's model.
     */
    protected $customFieldsCache = null;

    /**
     * Return the FQCN of the model that owns the custom fields.
     */
    abstract protected function customFieldsModel(): string;

    /**
     * Load the configured custom fields for the related model.
     */
    protected function customFields()
    {
        if ($this->customFieldsCache !== null) {
            return $this->customFieldsCache;
        }

        return $this->customFieldsCache = FieldModel::with('field.fieldOptions')
            ->where('model', $this->customFieldsModel())
            ->get()
            ->pluck('field')
            ->filter();
    }

    /**
     * Build validation rules for the custom fields.
     *
     * @return array<string, mixed>
     */
    protected function customFieldRules(): array
    {
        $rules = [];

        foreach ($this->customFields() as $field) {
            $key = "fields.{$field->id}";
            $required = (bool) ($field->required ?? false);

            switch ($field->type) {
                case 'text':
                    $rules[$key] = [$required ? 'required' : 'nullable', 'string', 'max:255'];
                    break;

                case 'textarea':
                    $rules[$key] = [$required ? 'required' : 'nullable', 'string'];
                    break;

                case 'date':
                    $rules[$key] = [$required ? 'required' : 'nullable', 'date'];
                    break;

                case 'checkbox':
                    $rules[$key] = [$required ? 'accepted' : 'nullable', 'boolean'];
                    break;

                case 'select':
                case 'radio':
                    $options = $field->fieldOptions->pluck('id')->map(fn ($id) => (string) $id)->all();
                    $rules[$key] = [
                        $required ? 'required' : 'nullable',
                        Rule::in($options),
                    ];
                    break;

                case 'checkbox_multiple':
                    $options = $field->fieldOptions->pluck('id')->map(fn ($id) => (string) $id)->all();
                    $rules[$key] = [$required ? 'required' : 'nullable', 'array'];
                    $rules["{$key}.*"] = [Rule::in($options)];
                    break;

                default:
                    $rules[$key] = [$required ? 'required' : 'nullable'];
                    break;
            }
        }

        return $rules;
    }

    /**
     * Build validation messages for the custom fields.
     *
     * @return array<string, string>
     */
    protected function customFieldMessages(): array
    {
        $messages = [];

        foreach ($this->customFields() as $field) {
            $label = ucfirst(__($field->name));
            $key = "fields.{$field->id}";

            $messages["{$key}.required"] = "The {$label} field is required.";
            $messages["{$key}.accepted"] = "The {$label} field is required.";
            $messages["{$key}.in"] = "The selected {$label} is invalid.";
            $messages["{$key}.date"] = "The {$label} must be a valid date.";
            $messages["{$key}.array"] = "The {$label} must be a valid selection.";
        }

        return $messages;
    }

    /**
     * Build validation attribute names for the custom fields.
     *
     * @return array<string, string>
     */
    protected function customFieldValidationAttributes(): array
    {
        $attributes = [];

        foreach ($this->customFields() as $field) {
            $attributes["fields.{$field->id}"] = ucfirst(__($field->name));
        }

        return $attributes;
    }
}
