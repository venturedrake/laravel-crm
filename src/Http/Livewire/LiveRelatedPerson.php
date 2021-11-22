<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;

class LiveRelatedPerson extends Component
{
    public $model;
    public $people;
    public $name;

    public function mount($model)
    {
        $this->model = $model;
        $this->people = $model->people;
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
        return view('laravel-crm::livewire.related-people');
    }
}
