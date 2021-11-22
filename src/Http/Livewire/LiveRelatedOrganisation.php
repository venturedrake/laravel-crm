<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;

class LiveRelatedOrganisation extends Component
{
    public $model;
    public $contacts;
    public $name;

    public function mount($model)
    {
        $this->model = $model;
        $this->contacts = $model->contacts;
    }

    public function link()
    {
        $data = $this->validate([
            'name' => 'required',
        ]);

        /*$this->model->notes()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'content' => $data['content'],
        ]);*/

        $this->resetFields();
    }

    private function resetFields()
    {
        $this->reset('name');
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.related-organisations');
    }
}
