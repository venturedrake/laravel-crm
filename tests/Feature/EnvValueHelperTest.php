<?php

// -----------------------------------------------------------------
// envValueIsTruthy() logic helpers
// -----------------------------------------------------------------

function isTruthy(string $envContents, string $key): bool
{
    if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $envContents, $m)) {
        return false;
    }

    $value = strtolower(trim($m[1], " \t\"'"));

    return in_array($value, ['true', '1', 'yes', 'on'], true);
}

// -----------------------------------------------------------------
// setEnvValue() / makeTempEnv() helpers
// -----------------------------------------------------------------

function setEnvValue(string $path, string $key, string $value): void
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

function makeTempEnv(string $contents): string
{
    $path = sys_get_temp_dir().'/laravel-crm-test-env-'.uniqid().'.env';
    file_put_contents($path, $contents);

    return $path;
}

// -----------------------------------------------------------------
// envValueIsTruthy tests
// -----------------------------------------------------------------

test('string true is truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=true\n", 'LARAVEL_CRM_TEAMS'))->toBeTrue();
});

test('integer one is truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=1\n", 'LARAVEL_CRM_TEAMS'))->toBeTrue();
});

test('yes is truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=yes\n", 'LARAVEL_CRM_TEAMS'))->toBeTrue();
});

test('on is truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=on\n", 'LARAVEL_CRM_TEAMS'))->toBeTrue();
});

test('double quoted true is truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=\"true\"\n", 'LARAVEL_CRM_TEAMS'))->toBeTrue();
});

test('single quoted true is truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS='true'\n", 'LARAVEL_CRM_TEAMS'))->toBeTrue();
});

test('uppercase true is truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=TRUE\n", 'LARAVEL_CRM_TEAMS'))->toBeTrue();
});

test('string false is not truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=false\n", 'LARAVEL_CRM_TEAMS'))->toBeFalse();
});

test('integer zero is not truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=0\n", 'LARAVEL_CRM_TEAMS'))->toBeFalse();
});

test('empty value is not truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=\n", 'LARAVEL_CRM_TEAMS'))->toBeFalse();
});

test('no is not truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=no\n", 'LARAVEL_CRM_TEAMS'))->toBeFalse();
});

test('off is not truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS=off\n", 'LARAVEL_CRM_TEAMS'))->toBeFalse();
});

test('missing key returns false', function () {
    expect(isTruthy("APP_ENV=production\n", 'LARAVEL_CRM_TEAMS'))->toBeFalse();
});

test('partial key match does not return truthy', function () {
    expect(isTruthy("LARAVEL_CRM_TEAMS_EXTRA=true\nAPP_ENV=production\n", 'LARAVEL_CRM_TEAMS'))->toBeFalse();
});

// -----------------------------------------------------------------
// setEnvValue tests
// -----------------------------------------------------------------

test('set env appends new key to file', function () {
    $path = makeTempEnv("APP_ENV=testing\n");
    setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

    $contents = file_get_contents($path);
    expect($contents)->toContain('LARAVEL_CRM_TEAMS=true')->toContain('APP_ENV=testing');

    unlink($path);
});

test('set env replaces existing key in place', function () {
    $path = makeTempEnv("APP_ENV=testing\nLARAVEL_CRM_TEAMS=false\n");
    setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

    $contents = file_get_contents($path);
    expect($contents)->toContain('LARAVEL_CRM_TEAMS=true')->not->toContain('LARAVEL_CRM_TEAMS=false');

    unlink($path);
});

test('set env does not duplicate key on multiple writes', function () {
    $path = makeTempEnv("LARAVEL_CRM_TEAMS=false\n");
    setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');
    setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

    expect(substr_count(file_get_contents($path), 'LARAVEL_CRM_TEAMS='))->toBe(1);

    unlink($path);
});

test('set env preserves other keys', function () {
    $path = makeTempEnv("APP_NAME=MyApp\nAPP_ENV=testing\nDB_CONNECTION=sqlite\n");
    setEnvValue($path, 'LARAVEL_CRM_TEAMS', 'true');

    $contents = file_get_contents($path);
    expect($contents)
        ->toContain('APP_NAME=MyApp')
        ->toContain('APP_ENV=testing')
        ->toContain('DB_CONNECTION=sqlite');

    unlink($path);
});

test('set env can update encryption flag', function () {
    $path = makeTempEnv("APP_ENV=testing\n");
    setEnvValue($path, 'LARAVEL_CRM_ENCRYPT_DB_FIELDS', 'true');

    expect(file_get_contents($path))->toContain('LARAVEL_CRM_ENCRYPT_DB_FIELDS=true');

    unlink($path);
});

test('set env overwrites encryption flag if already set', function () {
    $path = makeTempEnv("LARAVEL_CRM_ENCRYPT_DB_FIELDS=false\n");
    setEnvValue($path, 'LARAVEL_CRM_ENCRYPT_DB_FIELDS', 'true');

    $contents = file_get_contents($path);
    expect($contents)
        ->toContain('LARAVEL_CRM_ENCRYPT_DB_FIELDS=true')
        ->not->toContain('LARAVEL_CRM_ENCRYPT_DB_FIELDS=false');

    unlink($path);
});
