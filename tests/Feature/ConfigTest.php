<?php

test('default db table prefix is crm', function () {
    expect(config('laravel-crm.db_table_prefix'))->toBe('crm_');
});

test('default route prefix is crm', function () {
    expect(config('laravel-crm.route_prefix'))->toBe('crm');
});

test('default modules array includes all features', function () {
    $modules = config('laravel-crm.modules');

    expect($modules)->toBeArray()
        ->toContain('leads')
        ->toContain('deals')
        ->toContain('quotes')
        ->toContain('orders')
        ->toContain('invoices')
        ->toContain('deliveries')
        ->toContain('purchase-orders')
        ->toContain('teams')
        ->toContain('positions');
});

test('model with global includes settings', function () {
    expect(config('laravel-crm.model_with_global'))->toContain('settings');
});

test('user interface defaults to true', function () {
    expect((bool) config('laravel-crm.user_interface'))->toBeTrue();
});

test('encrypt db fields defaults to false', function () {
    expect((bool) config('laravel-crm.encrypt_db_fields'))->toBeFalse();
});

test('teams defaults to false', function () {
    expect((bool) config('laravel-crm.teams'))->toBeFalse();
});

test('docs url defaults to the laravel crm github url', function () {
    expect(config('laravel-crm.docs_url'))->toBe('https://github.com/venturedrake/laravel-crm');
});

test('upgrade guide url defaults to the published upgrade guide', function () {
    expect(config('laravel-crm.upgrade_guide_url'))->toBe('https://laravelcrm.com/docs/2.x/upgrading');
});

test('upgrade guide url honours the LARAVEL_CRM_UPGRADE_GUIDE_URL env var', function () {
    $override = 'https://docs.example.test/upgrading';

    putenv("LARAVEL_CRM_UPGRADE_GUIDE_URL={$override}");
    $_ENV['LARAVEL_CRM_UPGRADE_GUIDE_URL'] = $override;
    $_SERVER['LARAVEL_CRM_UPGRADE_GUIDE_URL'] = $override;

    try {
        $config = require __DIR__.'/../../config/laravel-crm.php';

        expect($config['upgrade_guide_url'])->toBe($override);
    } finally {
        putenv('LARAVEL_CRM_UPGRADE_GUIDE_URL');
        unset($_ENV['LARAVEL_CRM_UPGRADE_GUIDE_URL'], $_SERVER['LARAVEL_CRM_UPGRADE_GUIDE_URL']);
    }
});

test('docs url honours the LARAVEL_CRM_DOCS_URL env var', function () {
    // config/laravel-crm.php resolves env() when the file is loaded, so the
    // override can only be observed by re-evaluating the file with the var set —
    // mutating already-booted config would prove nothing about the env() call.
    $override = 'https://docs.example.test/crm';

    putenv("LARAVEL_CRM_DOCS_URL={$override}");
    $_ENV['LARAVEL_CRM_DOCS_URL'] = $override;
    $_SERVER['LARAVEL_CRM_DOCS_URL'] = $override;

    try {
        $config = require __DIR__.'/../../config/laravel-crm.php';

        expect($config['docs_url'])->toBe($override);
    } finally {
        putenv('LARAVEL_CRM_DOCS_URL');
        unset($_ENV['LARAVEL_CRM_DOCS_URL'], $_SERVER['LARAVEL_CRM_DOCS_URL']);
    }
});
