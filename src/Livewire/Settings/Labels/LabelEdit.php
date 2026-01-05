<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Labels;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Settings\Labels\Traits\HasLabelCommon;
use VentureDrake\LaravelCrm\Models\Label;

class LabelEdit extends Component
{
    use HasLabelCommon;

    public ?Label $label = null;

    public function mount()
    {
        $this->name = $this->label->name;
        $this->hex = $this->label->hex;
        $this->description = $this->label->description;
    }

    public function save()
    {
        $this->validate();

        $this->label->update([
            'name' => $this->name,
            'description' => $this->description,
            'hex' => $this->hex ?? '000000',
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.label_updated')),
            redirectTo: route('laravel-crm.labels.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.labels.label-edit');
    }
}
