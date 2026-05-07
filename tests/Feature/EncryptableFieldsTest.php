<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\TestCase;

class EncryptableFieldsTest extends TestCase
{
    public function test_encryption_disabled_stores_plain_values(): void
    {
        config()->set('laravel-crm.encrypt_db_fields', false);

        $person = Person::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $row = \DB::table('crm_people')->where('id', $person->id)->first();

        $this->assertSame('Jane', $row->first_name);
        $this->assertSame('Doe', $row->last_name);
    }

    public function test_encryption_enabled_stores_encrypted_values(): void
    {
        config()->set('laravel-crm.encrypt_db_fields', true);

        $person = Person::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $row = \DB::table('crm_people')->where('id', $person->id)->first();

        $this->assertNotSame('Jane', $row->first_name);
        $this->assertNotSame('Doe', $row->last_name);

        // The model still returns decrypted values via attribute access
        $this->assertSame('Jane', $person->fresh()->first_name);
        $this->assertSame('Doe', $person->fresh()->last_name);
    }
}
