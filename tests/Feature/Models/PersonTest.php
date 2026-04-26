<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Tests\TestCase;

class PersonTest extends TestCase
{
    public function test_person_uses_prefixed_people_table(): void
    {
        $this->assertSame('crm_people', (new Person)->getTable());
    }

    public function test_creating_a_person_assigns_external_id_uuid(): void
    {
        $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->assertTrue(Str::isUuid($person->external_id));
    }

    public function test_name_attribute_concatenates_first_and_last(): void
    {
        $person = Person::create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->assertSame('Jane Doe', $person->name);
    }

    public function test_name_attribute_trims_when_only_first_name(): void
    {
        $person = Person::create(['first_name' => 'Madonna']);

        $this->assertSame('Madonna', $person->name);
    }

    public function test_person_uses_soft_deletes(): void
    {
        $person = Person::create(['first_name' => 'Bye']);
        $person->delete();

        $this->assertSoftDeleted('crm_people', ['id' => $person->id]);
    }

    public function test_morph_relationships_defined(): void
    {
        $person = new Person;

        $this->assertInstanceOf(MorphMany::class, $person->emails());
        $this->assertInstanceOf(MorphMany::class, $person->phones());
        $this->assertInstanceOf(MorphMany::class, $person->addresses());
        $this->assertInstanceOf(MorphMany::class, $person->contacts());
    }
}
