<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Labels;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Livewire\Settings\Labels\Traits\HasLabelCommon;
use VentureDrake\LaravelCrm\Models\Label;

class LabelCreate extends Component
{
    use HasLabelCommon;

    public function save()
    {
        $this->validate();

        Label::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $this->name,
            'description' => $this->description,
            'hex' => $this->hex ?? '000000',
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.label_created')),
            redirectTo: route('laravel-crm.labels.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.labels.label-create');
    }
}
