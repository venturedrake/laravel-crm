<?php

namespace VentureDrake\LaravelCrm\Tests\Unit;

use PHPUnit\Framework\TestCase;

use function VentureDrake\LaravelCrm\Http\Helpers\Validate\email;

class ValidateHelperTest extends TestCase
{
    public function test_invalid_email_format_returns_null(): void
    {
        $this->assertNull(email('not-an-email'));
        $this->assertNull(email(''));
    }

    // Note: a valid email also requires DNS MX lookup which may or may not pass
    // depending on the test runner's network access; we only assert the failure
    // path which is fully deterministic.
}
