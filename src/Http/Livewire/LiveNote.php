<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;

class LiveNote extends Component
{
    public $model;
    public $notes;
    public $content;

    public function mount($model)
    {
        $this->model = $model;
        $this->getNotes();
    }

    public function create()
    {
        $data = $this->validate([
            'content' => 'required',
        ]);
        
        $this->model->notes()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'content' => $data['content'],
        ]);

        $this->resetFields();
    }
    
    private function getNotes()
    {
        $this->notes = $this->model->notes()->latest()->get();
    }

    private function resetFields()
    {
        $this->reset('content');
        $this->getNotes();
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.notes');
    }
}
