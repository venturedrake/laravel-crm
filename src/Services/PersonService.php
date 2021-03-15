<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Repositories\PersonRepository;

class PersonService
{
    /**
     * @var PersonRepository
     */
    private $personRepository;

    /**
     * LeadService constructor.
     * @param PersonRepository $personRepository
     */
    public function __construct(PersonRepository $personRepository)
    {
        $this->personRepository = $personRepository;
    }

    public function create($request)
    {
        $person = Person::create([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $request->title,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'birthday' => $request->birthday,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        if ($request->phone) {
            $person->phones()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => $request->phone,
                'type' => $request->phone_type,
                'primary' => 1,
            ]);
        }

        if ($request->email) {
            $person->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => 1,
            ]);
        }

        $person->addresses()->create([
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
        
        return $person;
    }

    public function createFromRelated($request)
    {
        $name = \VentureDrake\LaravelCrm\Http\Helpers\PersonName\firstLastFromName($request->person_name);
        
        $person = Person::create([
            'external_id' => Uuid::uuid4()->toString(),
            'first_name' => $name['first_name'],
            'last_name' => $name['last_name'] ?? null,
            'user_owner_id' => $request->user_owner_id ?? $request->user_assigned_id,
        ]);

        if ($request->phone) {
            $person->phones()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => $request->phone,
                'type' => $request->phone_type,
                'primary' => 1,
            ]);
        }

        if ($request->email) {
            $person->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => 1,
            ]);
        }
        
        return $person;
    }

    public function update(Person $person, $request)
    {
        $person->update([
            'title' => $request->title,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'birthday' => $request->birthday,
            'description' => $request->description,
            'user_owner_id' => $request->user_owner_id,
        ]);

        $email = $person->getPrimaryEmail();
        $phone = $person->getPrimaryPhone();
        $address = $person->getPrimaryAddress();

        if ($request->phone && $phone) {
            $phone->update([
                'number' => $request->phone,
                'type' => $request->phone_type,
            ]);
        } elseif ($request->phone) {
            $person->phones()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => $request->phone,
                'type' => $request->phone_type,
                'primary' => 1,
            ]);
        } elseif ($phone) {
            $phone->delete();
        }

        if ($request->email && $email) {
            $email->update([
                'address' => $request->email,
                'type' => $request->email_type,
            ]);
        } elseif ($request->email) {
            $person->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => 1,
            ]);
        } elseif ($email) {
            $email->delete();
        }

        if ($address) {
            $address->update([
                'line1' => $request->line1,
                'line2' => $request->line2,
                'line3' => $request->line3,
                'suburb' => $request->suburb,
                'state' => $request->state,
                'code' => $request->code,
                'country' => $request->country,
            ]);
        } else {
            $person->addresses()->create([
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
        }
        
        return $person;
    }
}
