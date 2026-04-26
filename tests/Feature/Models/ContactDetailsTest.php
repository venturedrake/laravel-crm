<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Tests\TestCase;

class ContactDetailsTest extends TestCase
{
    public function test_email_polymorphic_relation_to_person(): void
    {
        $person = Person::create(['first_name' => 'Jane']);

        $email = $person->emails()->create([
            'address' => 'jane@example.com',
            'type' => 'work',
            'primary' => true,
        ]);

        $this->assertSame('jane@example.com', $email->address);
        $this->assertTrue($email->primary);
        $this->assertSame($person->id, $email->emailable->id);
    }

    public function test_phone_polymorphic_relation_to_person(): void
    {
        $person = Person::create(['first_name' => 'Bob']);

        $phone = $person->phones()->create([
            'number' => '+61400000000',
            'type' => 'mobile',
            'primary' => true,
        ]);

        $this->assertSame('+61400000000', $phone->number);
        $this->assertSame('mobile', $phone->type);
        $this->assertSame($person->id, $phone->phoneable->id);
    }

    public function test_get_primary_email_returns_only_primary_record(): void
    {
        $person = Person::create(['first_name' => 'P']);
        $person->emails()->create(['address' => 'a@example.com', 'primary' => false]);
        $primary = $person->emails()->create(['address' => 'b@example.com', 'primary' => true]);

        $this->assertSame($primary->id, $person->getPrimaryEmail()->id);
    }

    public function test_get_primary_phone_returns_only_primary_record(): void
    {
        $person = Person::create(['first_name' => 'P']);
        $person->phones()->create(['number' => '111', 'primary' => false]);
        $primary = $person->phones()->create(['number' => '222', 'primary' => true]);

        $this->assertSame($primary->id, $person->getPrimaryPhone()->id);
    }

    public function test_email_uses_prefixed_table(): void
    {
        $this->assertSame('crm_emails', (new Email)->getTable());
    }

    public function test_phone_uses_prefixed_table(): void
    {
        $this->assertSame('crm_phones', (new Phone)->getTable());
    }
}
