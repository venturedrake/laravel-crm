<?php

namespace VentureDrake\LaravelCrm\Tests\Unit;

use PHPUnit\Framework\TestCase;

use function VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asArray;
use function VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\asRequest;

class PublicPropertiesHelperTest extends TestCase
{
    public function test_as_array_extracts_public_properties_only(): void
    {
        $object = new class
        {
            public string $name = 'Jane';

            public int $age = 30;

            protected string $secret = 'hidden';

            private string $token = 'token';
        };

        $array = asArray($object);

        $this->assertSame(['name' => 'Jane', 'age' => 30], $array);
    }

    public function test_as_request_returns_a_request_with_public_properties(): void
    {
        $object = new class
        {
            public string $title = 'Big Lead';

            public ?string $description = null;
        };

        $request = asRequest($object);

        $this->assertSame('Big Lead', $request->input('title'));
        $this->assertNull($request->input('description'));
    }
}
