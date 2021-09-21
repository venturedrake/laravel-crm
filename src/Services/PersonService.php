<?php

namespace VentureDrake\LaravelCrm\Services;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
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

        $this->updatePersonPhones($person, $request->phones);
        $this->updatePersonEmails($person, $request->emails);

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
                'primary' => (($request->phone_primary) ? 1 : 0),
            ]);
        }

        if ($request->email) {
            $person->emails()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => $request->email,
                'type' => $request->email_type,
                'primary' => (($request->email_primary) ? 1 : 0),
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
        
        $this->updatePersonPhones($person, $request->phones);
        $this->updatePersonEmails($person, $request->emails);
        
        $address = $person->getPrimaryAddress();

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
    
    protected function updatePersonPhones($person, $phones)
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
                    $phone = $person->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'] ,
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                }
            }
        }
        
        foreach ($person->phones as $phone) {
            if (! in_array($phone->id, $phoneIds)) {
                $phone->delete();
            }
        }
    }
    
    protected function updatePersonEmails($person, $emails)
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
                    $email = $person->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'] ,
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                }
            }
        }

        foreach ($person->emails as $email) {
            if (! in_array($email->id, $emailIds)) {
                $email->delete();
            }
        }
    }
}
