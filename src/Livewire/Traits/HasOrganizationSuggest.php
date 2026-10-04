<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use VentureDrake\LaravelCrm\Models\Organization;

trait HasOrganizationSuggest
{
    use SearchesEncryptableContacts;

    public $organizations;

    public $showOrganizations = false;

    public function searchOrganizations()
    {
        if (! empty($this->organization_name)) {
            if ($this->encryptionEnabled()) {
                // Names are stored encrypted, so neither LIKE nor ORDER BY on
                // the column means anything — match and sort on decrypted values.
                $this->organizations = Organization::whereIn('id', $this->matchingOrganizationIds($this->organization_name))
                    ->get()
                    ->sortBy(fn ($organization) => strtolower((string) $organization->name))
                    ->take(10)
                    ->values();
            } else {
                $this->organizations = Organization::orderby('name', 'asc')
                    ->select('*')
                    ->where('name', 'like', '%'.$this->organization_name.'%')
                    ->limit(10)
                    ->get();
            }

            $this->showOrganizations = $this->organizations->isNotEmpty();
        } else {
            $this->showOrganizations = false;
        }
    }

    public function linkOrganization($id)
    {
        if ($organization = Organization::find($id)) {
            $this->organization_id = $id;
            $this->organization_name = $organization->name;

            if (method_exists($this, 'generateTitleString')) {
                $this->generateTitleString($organization->name);
            }
        }

        $this->showOrganizations = false;
    }

    public function hideOrganizations()
    {
        $this->showOrganizations = false;
    }
}
