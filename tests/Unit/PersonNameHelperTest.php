<?php

namespace VentureDrake\LaravelCrm\Tests\Unit;

use PHPUnit\Framework\TestCase;

use function VentureDrake\LaravelCrm\Http\Helpers\PersonName\firstLastFromName;
use function VentureDrake\LaravelCrm\Http\Helpers\PersonName\firstNameFromName;

class PersonNameHelperTest extends TestCase
{
    public function test_first_last_from_name_splits_two_parts(): void
    {
        $result = firstLastFromName('Jane Doe');

        $this->assertSame('Jane', $result['first_name']);
        $this->assertSame('Doe', $result['last_name']);
    }

    public function test_first_last_from_name_handles_multiple_first_names(): void
    {
        $result = firstLastFromName('Mary Jane Smith');

        $this->assertSame('Mary Jane', $result['first_name']);
        $this->assertSame('Smith', $result['last_name']);
    }

    public function test_first_last_from_name_handles_single_token(): void
    {
        $result = firstLastFromName('Madonna');

        $this->assertSame('', $result['first_name']);
        $this->assertSame('Madonna', $result['last_name']);
    }

    public function test_first_name_from_name_returns_first_name(): void
    {
        $this->assertSame('Mary Jane', firstNameFromName('Mary Jane Smith'));
    }

    public function test_first_name_from_name_returns_null_for_single_word(): void
    {
        $this->assertNull(firstNameFromName('Madonna'));
    }
}
