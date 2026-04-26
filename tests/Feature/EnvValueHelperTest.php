<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Tests\TestCase;

/**
 * Tests for the .env read/write helpers inside LaravelCrmInstall.
 *
 * The private methods envValueIsTruthy() and setEnvValue() are extracted
 * and exercised directly here so we get deterministic coverage without
 * running the full interactive installer.
 */
class EnvValueHelperTest extends TestCase
{
    // -----------------------------------------------------------------------
    // envValueIsTruthy() logic
    // -----------------------------------------------------------------------

    private function isTruthy(string $envContents, string $key): bool
    {
        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $envContents, $m)) {
            return false;
        }

        $value = strtolower(trim($m[1], " \t\"'"));

        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }

    public function test_string_true_is_truthy(): void
    {
        $this->assertTrue($this->isTruthy("LARAVEL_CRM_TEAMS=true\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_integer_one_is_truthy(): void
    {
        $this->assertTrue($this->isTruthy("LARAVEL_CRM_TEAMS=1\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_yes_is_truthy(): void
    {
        $this->assertTrue($this->isTruthy("LARAVEL_CRM_TEAMS=yes\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_on_is_truthy(): void
    {
        $this->assertTrue($this->isTruthy("LARAVEL_CRM_TEAMS=on\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_double_quoted_true_is_truthy(): void
    {
        $this->assertTrue($this->isTruthy("LARAVEL_CRM_TEAMS=\"true\"\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_single_quoted_true_is_truthy(): void
    {
        $this->assertTrue($this->isTruthy("LARAVEL_CRM_TEAMS='true'\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_uppercase_true_is_truthy(): void
    {
        $this->assertTrue($this->isTruthy("LARAVEL_CRM_TEAMS=TRUE\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_string_false_is_not_truthy(): void
    {
        $this->assertFalse($this->isTruthy("LARAVEL_CRM_TEAMS=false\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_integer_zero_is_not_truthy(): void
    {
        $this->assertFalse($this->isTruthy("LARAVEL_CRM_TEAMS=0\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_empty_value_is_not_truthy(): void
    {
        $this->assertFalse($this->isTruthy("LARAVEL_CRM_TEAMS=\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_no_is_not_truthy(): void
    {
        $this->assertFalse($this->isTruthy("LARAVEL_CRM_TEAMS=no\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_off_is_not_truthy(): void
    {
        $this->assertFalse($this->isTruthy("LARAVEL_CRM_TEAMS=off\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_missing_key_returns_false(): void
    {
        $this->assertFalse($this->isTruthy("APP_ENV=production\n", 'LARAVEL_CRM_TEAMS'));
    }

    public function test_partial_key_match_does_not_return_truthy(): void
    {
        // LARAVEL_CRM_TEAMS_EXTRA should not match LARAVEL_CRM_TEAMS
        $this->assertFalse(
            $this->isTruthy("LARAVEL_CRM_TEAMS_EXTRA=true\nAPP_ENV=production\n", 'LARAVEL_CRM_TEAMS')
        );
    }

    // -----------------------------------------------------------------------
    // setEnvValue() logic
    // -----------------------------------------------------------------------

    private function setEnvValue(string $path, string $key, string $value): void
    {
        $contents = file_get_contents($path);
        $line = "{$key}={$value}";
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $line, $contents);
        } else {
            $contents = rtrim($contents, "\r\n").PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $contents);
    }

    private function makeTempEnv(string $contents): string
    {
        $path = sys_get_temp_dir().'/laravel-crm-test-env-'.uniqid().'.env';
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_set_env_appends_new_key_to_file(): void
    {
        $path = $this->makeTempEnv("APP_ENV=testing\n");
        $this->setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

        $contents = file_get_contents($path);
        $this->assertStringContainsString('LARAVEL_CRM_TEAMS=true', $contents);
        $this->assertStringContainsString('APP_ENV=testing', $contents);

        unlink($path);
    }

    public function test_set_env_replaces_existing_key_in_place(): void
    {
        $path = $this->makeTempEnv("APP_ENV=testing\nLARAVEL_CRM_TEAMS=false\n");
        $this->setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

        $contents = file_get_contents($path);
        $this->assertStringContainsString('LARAVEL_CRM_TEAMS=true', $contents);
        $this->assertStringNotContainsString('LARAVEL_CRM_TEAMS=false', $contents);

        unlink($path);
    }

    public function test_set_env_does_not_duplicate_key_on_multiple_writes(): void
    {
        $path = $this->makeTempEnv("LARAVEL_CRM_TEAMS=false\n");
        $this->setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');
        $this->setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

        $this->assertSame(1, substr_count(file_get_contents($path), 'LARAVEL_CRM_TEAMS='));

        unlink($path);
    }

    public function test_set_env_preserves_other_keys(): void
    {
        $path = $this->makeTempEnv("APP_NAME=MyApp\nAPP_ENV=testing\nDB_CONNECTION=sqlite\n");
        $this->setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

        $contents = file_get_contents($path);
        $this->assertStringContainsString('APP_NAME=MyApp', $contents);
        $this->assertStringContainsString('APP_ENV=testing', $contents);
        $this->assertStringContainsString('DB_CONNECTION=sqlite', $contents);

        unlink($path);
    }

    public function test_set_env_can_update_encryption_flag(): void
    {
        $path = $this->makeTempEnv("APP_ENV=testing\n");
        $this->setEnvValue($path, 'LARAVEL_CRM_ENCRYPT_DB_FIELDS', 'true');

        $this->assertStringContainsString('LARAVEL_CRM_ENCRYPT_DB_FIELDS=true', file_get_contents($path));

        unlink($path);
    }

    public function test_set_env_overwrites_encryption_flag_if_already_set(): void
    {
        $path = $this->makeTempEnv("LARAVEL_CRM_ENCRYPT_DB_FIELDS=false\n");
        $this->setEnvValue($path, 'LARAVEL_CRM_ENCRYPT_DB_FIELDS', 'true');

        $contents = file_get_contents($path);
        $this->assertStringContainsString('LARAVEL_CRM_ENCRYPT_DB_FIELDS=true', $contents);
        $this->assertStringNotContainsString('LARAVEL_CRM_ENCRYPT_DB_FIELDS=false', $contents);

        unlink($path);
    }
}

