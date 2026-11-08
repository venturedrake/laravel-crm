<?php

/**
 * Every literal laravel-crm::lang key used in src/ or resources/views/ must
 * exist in en/lang.php. A missing key renders as the raw key (e.g.
 * "Laravel-crm::lang.label_created") once ucfirst() is applied, which is how
 * issue #112 shipped.
 *
 * Only en is asserted on: en_au and en_gb are override-only files that fall
 * back to en, and so does fa for any key it lacks.
 */
function langKeysRoot(): string
{
    return realpath(__DIR__.'/../..');
}

/**
 * Views that reference keys which do not exist, but which are never rendered.
 * Both partials are only included from resources/v1/views/layouts/app.blade.php,
 * and the service provider never registers the v1 views, so they are dead code
 * (they still mention the long-removed "projects" and "clients" keys).
 */
function langKeysExcludedFiles(): array
{
    return [
        'resources/views/layouts/partials/nav.blade.php',
        'resources/views/layouts/partials/search.blade.php',
    ];
}

/**
 * @return array<string, list<string>> key => files that use it
 */
function langKeysUsed(): array
{
    $root = langKeysRoot();
    $excluded = langKeysExcludedFiles();
    $used = [];

    // The closing quote must be followed by "," or ")", so concatenated
    // dynamic keys such as 'laravel-crm::lang.pdf_template_'.$slug are skipped.
    $pattern = '/(?:\b__|\btrans_choice|\btrans|Lang::get|@lang)\(\s*([\'"])laravel-crm::lang\.([A-Za-z0-9_.]+)\1\s*[,)]/';

    foreach (['src', 'resources/views'] as $dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'/'.$dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = ltrim(str_replace($root, '', $file->getPathname()), '/');

            if (in_array($relative, $excluded, true)) {
                continue;
            }

            // Commented-out Blade markup is never rendered.
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($file->getPathname()));

            preg_match_all($pattern, $source, $matches);

            foreach ($matches[2] as $key) {
                $used[$key][] = $relative;
            }
        }
    }

    ksort($used);

    return array_map(fn ($files) => array_values(array_unique($files)), $used);
}

test('the scan finds the lang keys in use', function () {
    // Guards against a broken regex or path silently matching nothing.
    expect(count(langKeysUsed()))->toBeGreaterThan(500);
});

test('the excluded files still exist', function () {
    // If a dead view is deleted or moved, drop it from the exclusion list.
    foreach (langKeysExcludedFiles() as $file) {
        expect(file_exists(langKeysRoot().'/'.$file))->toBeTrue("Excluded file {$file} no longer exists.");
    }
});

test('every literal lang key used in src and views exists in en', function () {
    $lang = require langKeysRoot().'/resources/lang/en/lang.php';

    $missing = [];

    foreach (langKeysUsed() as $key => $files) {
        $value = $lang[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            foreach ($files as $file) {
                $missing[] = "{$key} <- {$file}";
            }
        }
    }

    expect($missing)->toBe([], "Missing keys in resources/lang/en/lang.php:\n".implode("\n", $missing));
});

test('the created toasts from issue 112 resolve to text', function () {
    foreach ([
        'label_created',
        'product_category_created',
        'organization_created',
        'person_created',
        'task_created',
        'delivery_created',
    ] as $key) {
        expect(__('laravel-crm::lang.'.$key))->not->toContain('laravel-crm::lang.');
    }
});
