<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Organization;

class LiveRelatedContactOrganization extends Component
{
    public $model;

    public $contacts;

    public $organization_id;

    public $organization_name;

    public $actions;

    public $contactTypeFilter;

    public function mount($model, $actions = true, $contactTypeFilter = null)
    {
        $this->model = $model;
        $this->actions = $actions;
        $this->contactTypeFilter = $contactTypeFilter;
        $this->getContacts();
    }

    public function link()
    {
        $data = $this->validate([
            'organization_name' => 'required',
        ]);

        if ($this->organization_id) {
            $organization = Organization::find($this->organization_id);
        } else {
            $organization = Organization::create([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => $data['organization_name'],
                'user_owner_id' => auth()->user()->id,
            ]);
        }

        $this->model->contacts()->create([
            'entityable_type' => $organization->getMorphClass(),
            'entityable_id' => $organization->id,
        ]);

        $organization->contacts()->create([
            'entityable_type' => $this->model->getMorphClass(),
            'entityable_id' => $this->model->id,
        ]);

        $this->resetFields();

        $this->getContacts();

        $this->dispatchBrowserEvent('linkedOrganization');
    }

    public function remove($id)
    {
        if ($organization = Organization::find($id)) {
            $this->model->contacts()
                ->where([
                    'entityable_type' => $organization->getMorphClass(),
                    'entityable_id' => $organization->id,
                ])
                ->delete();

            $organization->contacts()
                ->where([
                    'entityable_type' => $this->model->getMorphClass(),
                    'entityable_id' => $this->model->id,
                ])
                ->delete();
        }

        $this->getContacts();

        $this->dispatchBrowserEvent('linkedOrganization');
    }

    public function updatedOrganizationName($value)
    {
        $this->dispatchBrowserEvent('updatedNameFieldAutocomplete');
    }

    private function getContacts()
    {
        $this->contacts = $this->model
            ->contacts()
            ->when($this->contactTypeFilter, function ($query) {
                return $query->leftJoin('contact_contact_type', 'contact_contact_type.contact_id', '=', 'contacts.id')
                    ->leftJoin('contact_types', 'contact_contact_type.contact_type_id', '=', 'contact_types.id')
                    ->where('contact_types.name', $this->contactTypeFilter);
            })
            ->where('entityable_type', 'LIKE', '%Organization%')
            ->get();
    }

    private function resetFields()
    {
        $this->reset('organization_id', 'organization_name');
    }

    public function render()
    {
        return view('laravel-crm::livewire.related-contact-organizations');
    }
}
