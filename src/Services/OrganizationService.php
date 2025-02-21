<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Repositories\OrganizationRepository;

class OrganizationService
{
    /**
     * @var OrganizationRepository
     */
    private $organizationRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(OrganizationRepository $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    public function create($request)
    {
        $organization = Organization::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
            'organization_type_id' => $request->organization_type_id,
            'vat_number' => $request->vat_number,
            'industry_id' => $request->industry_id,
            'timezone_id' => $request->timezone_id,
            'number_of_employees' => $request->number_of_employees,
            'annual_revenue' => $request->annual_revenue,
            'total_money_raised' => $request->total_money_raised,
            'linkedin' => $request->linkedin,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $this->updateOrganizationPhones($organization, $request->phones);
        $this->updateOrganizationEmails($organization, $request->emails);
        $this->updateOrganizationAddresses($organization, $request->addresses);

        return $organization;
    }

    public function createFromRelated($request)
    {
        $organization = Organization::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->organization_name,
            'organization_type_id' => $request->organization_type_id,
            'vat_number' => $request->vat_number,
            'industry_id' => $request->industry_id,
            'timezone_id' => $request->timezone_id,
            'number_of_employees' => $request->number_of_employees,
            'total_money_raised' => $request->total_money_raised,
            'linkedin' => $request->linkedin,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        $organization->addresses()->create([
            'external_id' => Uuid::uuid4()->toString(),
            'line1' => $request->line1,
            'line2' => $request->line2,
            'line3' => $request->line3,
            'suburb' => $request->suburb,
            'state' => $request->state,
            'code' => $request->code,
            'country' => $request->country,
            'primary' => 1,
        ]);

        return $organization;
    }

    public function update(Organization $organization, $request)
    {
        $organization->update([
            'name' => $request->name,
            'organization_type_id' => $request->organization_type_id,
            'vat_number' => $request->vat_number,
            'industry_id' => $request->industry_id,
            'timezone_id' => $request->timezone_id,
            'number_of_employees' => $request->number_of_employees,
            'annual_revenue' => $request->annual_revenue,
            'total_money_raised' => $request->total_money_raised,
            'linkedin' => $request->linkedin,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $this->updateOrganizationPhones($organization, $request->phones);
        $this->updateOrganizationEmails($organization, $request->emails);
        $this->updateOrganizationAddresses($organization, $request->addresses);

        return $organization;
    }

    protected function updateOrganizationPhones($organization, $phones)
    {
        $phoneIds = [];
        if ($phones) {
            foreach ($phones as $phoneRequest) {
                if ($phoneRequest['id'] && $phone = Phone::find($phoneRequest['id'])) {
                    $phone->update([
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                } elseif ($phoneRequest['number']) {
                    $phone = $organization->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                }
            }
        }

        foreach ($organization->phones as $phone) {
            if (! in_array($phone->id, $phoneIds)) {
                $phone->delete();
            }
        }
    }

    protected function updateOrganizationEmails($organization, $emails)
    {
        $emailIds = [];

        if ($emails) {
            foreach ($emails as $emailRequest) {
                if ($emailRequest['id'] && $email = Email::find($emailRequest['id'])) {
                    $email->update([
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                } elseif ($emailRequest['address']) {
                    $email = $organization->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                }
            }
        }

        foreach ($organization->emails as $email) {
            if (! in_array($email->id, $emailIds)) {
                $email->delete();
            }
        }
    }

    protected function updateOrganizationAddresses($organization, $addresses)
    {
        $addressIds = [];

        if ($addresses) {
            foreach ($addresses as $addressRequest) {
                if ($addressRequest['id'] && $address = Address::find($addressRequest['id'])) {
                    $address->update([
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                } else {
                    $address = $organization->addresses()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                }
            }
        }

        foreach ($organization->addresses as $address) {
            if (! in_array($address->id, $addressIds)) {
                $address->delete();
            }
        }
    }
}
