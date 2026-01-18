<?php

namespace VentureDrake\LaravelCrm\Livewire\Organizations;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Organizations\Traits\HasOrganizationCommon;
use VentureDrake\LaravelCrm\Models\Organization;

class OrganizationEdit extends Component
{
    use HasOrganizationCommon;

    public Organization $organization;

    public function mount()
    {
        $this->mountCommon();

        $this->name = $this->organization->name;
        $this->organization_type_id = $this->organization->organization_type_id;
        $this->vat_number = $this->organization->vat_number;
        $this->industry_id = $this->organization->industry_id;
        $this->timezone_id = $this->organization->timezone_id;
        $this->number_of_employees = $this->organization->number_of_employees;
        $this->annual_revenue = $this->organization->annual_revenue / 100;
        $this->linkedin = $this->organization->linkedin;
        $this->description = $this->organization->description;
        $this->labels = $this->organization->labels()->pluck('id')->toArray();
        $this->user_owner_id = $this->organization->user_owner_id;

        if ($this->organization->phones->count() == 0) {
            $this->addPhone();
        } else {
            foreach ($this->organization->phones as $phone) {
                $this->phones[] = [
                    'id' => $phone->id,
                    'type' => $phone->type,
                    'number' => $phone->number,
                    'primary' => $phone->primary,
                ];
            }
        }

        if ($this->organization->emails->count() == 0) {
            $this->addEmail();
        } else {
            foreach ($this->organization->emails as $email) {
                $this->emails[] = [
                    'id' => $email->id,
                    'type' => $email->type,
                    'address' => $email->address,
                    'primary' => $email->primary,
                ];
            }
        }

        if ($this->organization->addresses->count() == 0) {
            $this->addAddress();
        } else {
            foreach ($this->organization->addresses as $address) {
                $this->addresses[] = [
                    'id' => $address->id,
                    'type' => $address->address_type_id,
                    'primary' => $address->primary,
                    'name' => $address->name,
                    'contact' => $address->contact,
                    'phone' => $address->phone,
                    'address' => $address->address,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'line3' => $address->line3,
                    'city' => $address->city,
                    'state' => $address->state,
                    'code' => $address->code,
                    'country' => $address->country,
                ];
            }
        }
    }

    public function save()
    {
        $this->validate();

        // Create a request object to pass to services
        $request = \VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest($this);

        $this->organizationService->update($this->organization, $request);

        $this->organization->labels()->sync($request->labels ?? []);

        $this->success(
            ucfirst(trans('laravel-crm::lang.organization_updated')),
            redirectTo: route('laravel-crm.organizations.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.organizations.organization-edit');
    }
}
