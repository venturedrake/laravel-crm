<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Models\Organization;

class RelatedOrganizations extends Component
{
    use HasOrganizationSuggest;
    use Toast;

    public $showAddRelatedOrganization = false;

    public $model = null;

    public $organization_id;

    public $organization_name;

    #[Computed, On('related-contacts-updated')]
    public function contacts()
    {
        return $this->model
            ->contacts()
            ->when(! empty($this->contactTypeFilter), function ($query) {
                return $query->leftJoin('contact_contact_type', 'contact_contact_type.contact_id', '=', 'contacts.id')
                    ->leftJoin('contact_types', 'contact_contact_type.contact_type_id', '=', 'contact_types.id')
                    ->where('contact_types.name', $this->contactTypeFilter);
            })
            ->where('entityable_type', 'LIKE', '%Organization%')
            ->get();
    }

    public function add()
    {
        $data = $this->validate([
            'organization_name' => 'required',
        ]);

        if ($this->organization_id) {
            $organization = Organization::find($this->organization_id);
        } else {
            $organization = Organization::create([
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

        $this->showAddRelatedOrganization = false;
        $this->reset('organization_id', 'organization_name');

        $this->success(
            ucfirst(trans('laravel-crm::lang.related_contact_added'))
        );

        $this->dispatch('related-contacts-updated');
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

            $this->success(
                ucfirst(trans('laravel-crm::lang.related_contact_removed'))
            );

            $this->dispatch('related-contacts-updated');
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.related-organizations');
    }
}
