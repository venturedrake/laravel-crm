<?php

namespace VentureDrake\LaravelCrm\Tests\Unit;

use PHPUnit\Framework\TestCase;

use function VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressMultipleLines;
use function VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine;

class AddressLineHelperTest extends TestCase
{
    private function makeAddress(array $attrs): object
    {
        return (object) array_merge([
            'line' => null,
            'line1' => null,
            'line2' => null,
            'line3' => null,
            'city' => null,
            'state' => null,
            'code' => null,
            'country' => null,
        ], $attrs);
    }

    public function test_address_single_line_returns_line_when_provided(): void
    {
        $address = $this->makeAddress(['line' => '1 Main St, Sydney NSW 2000']);

        $this->assertSame('1 Main St, Sydney NSW 2000', addressSingleLine($address));
    }

    public function test_address_single_line_builds_from_components(): void
    {
        $address = $this->makeAddress([
            'line1' => '1 Main St',
            'line2' => 'Suite 100',
            'city' => 'Sydney',
            'state' => 'NSW',
            'code' => '2000',
            'country' => 'Australia',
        ]);

        $this->assertSame('1 Main St, Suite 100, Sydney, NSW 2000 Australia', addressSingleLine($address));
    }

    public function test_address_multiple_lines_uses_newlines(): void
    {
        $address = $this->makeAddress([
            'line1' => '1 Main St',
            'line2' => 'Suite 100',
            'city' => 'Sydney',
            'state' => 'NSW',
            'code' => '2000',
            'country' => 'Australia',
        ]);

        $expected = '1 Main St'.PHP_EOL.'Suite 100'.PHP_EOL.'Sydney NSW 2000'.PHP_EOL.'Australia';

        $this->assertSame($expected, addressMultipleLines($address));
    }
}
