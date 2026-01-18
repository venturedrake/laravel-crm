<?php

namespace VentureDrake\LaravelCrm\Livewire\Organizations;

use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Organization;

class OrganizationShow extends Component
{
    public Organization $organization;

    public function delete($id)
    {
        if ($organization = Organization::find($id)) {
            $organization->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.organization_deleted')), redirectTo: route('laravel-crm.organizations.index'));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.organizations.organization-show');
    }
}
