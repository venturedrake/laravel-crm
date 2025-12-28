<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use VentureDrake\LaravelCrm\Models\Organization;

trait HasOrganizationSuggest
{
    public $organizations;

    public $showOrganizations = false;

    public function searchOrganizations()
    {
        if (! empty($this->organization_name)) {

            $this->organizations = Organization::orderby('name', 'asc')
                ->select('*')
                ->where('name', 'like', '%'.$this->organization_name.'%')
                ->limit(10)
                ->get();

            if ($this->organizations->count() > 0) {
                $this->showOrganizations = true;
            }
        } else {
            $this->showOrganizations = false;
        }
    }

    public function linkOrganization($id)
    {
        if ($organization = Organization::find($id)) {
            $this->organization_id = $id;
            $this->organization_name = $organization->name;
        }

        $this->showOrganizations = false;
    }

    public function hideOrganizations()
    {
        $this->showOrganizations = false;
    }
}
