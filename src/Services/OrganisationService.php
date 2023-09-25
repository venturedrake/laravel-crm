<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Repositories\OrganisationRepository;

class OrganisationService
{
    /**
     * @var OrganisationRepository
     */
    private $organisationRepository;

    /**
     * LeadService constructor.
     * @param OrganisationRepository $organisationRepository
     */
    public function __construct(OrganisationRepository $organisationRepository)
    {
        $this->organisationRepository = $organisationRepository;
    }

    public function create($request)
    {
        $organisation = Organisation::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
            'organisation_type_id' => $request->organisation_type_id,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $this->updateOrganisationPhones($organisation, $request->phones);
        $this->updateOrganisationEmails($organisation, $request->emails);
        $this->updateOrganisationAddresses($organisation, $request->addresses);

        return $organisation;
    }

    public function createFromRelated($request)
    {
        $organisation = Organisation::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->organisation_name,
            'user_owner_id' => $request->user_owner_id ?? auth()->user()->id,
        ]);

        $organisation->addresses()->create([
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

        return $organisation;
    }

    public function update(Organisation $organisation, $request)
    {
        $organisation->update([
            'name' => $request->name,
            'organisation_type_id' => $request->organisation_type_id,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $this->updateOrganisationPhones($organisation, $request->phones);
        $this->updateOrganisationEmails($organisation, $request->emails);
        $this->updateOrganisationAddresses($organisation, $request->addresses);

        return $organisation;
    }

    protected function updateOrganisationPhones($organisation, $phones)
    {
        $phoneIds = [];
        if ($phones) {
            foreach ($phones as $phoneRequest) {
                if ($phoneRequest['id'] && $phone = Phone::find($phoneRequest['id'])) {
                    $phone->update([
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'] ,
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                } elseif ($phoneRequest['number']) {
                    $phone = $organisation->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'] ,
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                }
            }
        }

        foreach ($organisation->phones as $phone) {
            if (! in_array($phone->id, $phoneIds)) {
                $phone->delete();
            }
        }
    }

    protected function updateOrganisationEmails($organisation, $emails)
    {
        $emailIds = [];

        if ($emails) {
            foreach ($emails as $emailRequest) {
                if ($emailRequest['id'] && $email = Email::find($emailRequest['id'])) {
                    $email->update([
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'] ,
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                } elseif ($emailRequest['address']) {
                    $email = $organisation->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'] ,
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                }
            }
        }

        foreach ($organisation->emails as $email) {
            if (! in_array($email->id, $emailIds)) {
                $email->delete();
            }
        }
    }

    protected function updateOrganisationAddresses($organisation, $addresses)
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
                    $address = $organisation->addresses()->create([
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

        foreach ($organisation->addresses as $address) {
            if (! in_array($address->id, $addressIds)) {
                $address->delete();
            }
        }
    }
}
