<?php

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

/**
 * laravelcrm:upgrade is the half of the update the host's composer hook fires,
 * so it runs unattended on production boxes mid-deploy. The constraints tested
 * here are the ones that make that safe: no database, no prompts, and a build
 * that is not ready to publish into is not a failure.
 *
 * The public path is a throwaway directory that starts out *absent*, which is
 * also the "the build has not created public/ yet" case — tests that need it
 * present call scratchPublicPath().
 *
 * This lives in its own suite rather than under tests/Feature because Pest
 * binds one base test case per directory tree and cannot override it for a
 * nested folder — and the public path has to be set before the service provider
 * boots, since it evaluates public_path() when registering its publish map.
 */

/** The scratch public path, created on first use. */
function scratchPublicPath(): string
{
    $path = public_path();

    if (! is_dir($path)) {
        mkdir($path, 0755, true);
    }

    return $path;
}

/** The build output this package ships, whatever the current content hashes are. */
function packagedBuildAssets(): array
{
    return array_map(
        fn ($file) => $file->getFilename(),
        (new Filesystem)->files(__DIR__.'/../../public/vendor/laravel-crm/assets')
    );
}

// -----------------------------------------------------------------------
// Registration
// -----------------------------------------------------------------------

test('upgrade command is registered', function () {
    expect(app(Kernel::class)->all())->toHaveKey('laravelcrm:upgrade');
});

test('upgrade command takes no options that would make it prompt', function () {
    // Nothing to answer inside a composer hook.
    $definition = app(Kernel::class)->all()['laravelcrm:upgrade']->getDefinition();

    expect($definition->getArguments())->toBe([]);
});

// -----------------------------------------------------------------------
// The database constraint
// -----------------------------------------------------------------------

test('upgrade makes no database queries at all', function () {
    // The whole reason this command is separate from laravelcrm:update. During
    // a build the database may be unreachable, mid-migration, or belong to a
    // different release — so not one query, not even a Setting read.
    $queries = [];

    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    scratchPublicPath();

    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    expect($queries)->toBe([]);
});

// -----------------------------------------------------------------------
// Publishing
// -----------------------------------------------------------------------

test('upgrade publishes the built assets into the public path', function () {
    $path = scratchPublicPath();
    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    foreach (packagedBuildAssets() as $filename) {
        expect(file_exists($path.'/vendor/laravel-crm/assets/'.$filename))
            ->toBeTrue("Expected {$filename} to be published.");
    }

    expect(file_exists($path.'/vendor/laravel-crm/manifest.json'))->toBeTrue();

});

test('upgrade publishes over an existing copy rather than skipping it', function () {
    // --force, so a host already carrying last release's bundle actually gets
    // the new one. Without it vendor:publish leaves existing files alone and
    // the CRM keeps serving stale CSS.
    $path = scratchPublicPath();
    $manifest = $path.'/vendor/laravel-crm/manifest.json';

    mkdir(dirname($manifest), 0755, true);
    file_put_contents($manifest, '{"stale":true}');

    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    expect(file_get_contents($manifest))->not->toContain('stale');

});

// -----------------------------------------------------------------------
// Pruning
// -----------------------------------------------------------------------

test('upgrade prunes build output the package no longer ships', function () {
    // Content-hashed filenames: --force overwrites and adds but never removes,
    // so without this a host accumulates one app-<hash>.js per release it has
    // ever installed while manifest.json names exactly one of them.
    $path = scratchPublicPath();
    $assets = $path.'/vendor/laravel-crm/assets';

    mkdir($assets, 0755, true);
    file_put_contents($assets.'/app-STALEHASH.js', '// last release');
    file_put_contents($assets.'/app-OTHERHASH.css', '/* last release */');

    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    expect(file_exists($assets.'/app-STALEHASH.js'))->toBeFalse()
        ->and(file_exists($assets.'/app-OTHERHASH.css'))->toBeFalse();

    foreach (packagedBuildAssets() as $filename) {
        expect(file_exists($assets.'/'.$filename))->toBeTrue();
    }

});

test('upgrade prunes only after the publish has succeeded', function () {
    // Ordering, not decoration. The bundle is content-hashed, so on a host
    // coming from an older release *every* file in assets/ is stale — pruning
    // first empties the directory, and a publish that then failed would leave
    // the host with no JS or CSS at all, where it would otherwise still be
    // serving the previous release's working bundle.
    //
    // Asserted against the source because the failure it guards needs a
    // vendor:publish that fails midway, which is not reachable from a test.
    $source = file_get_contents(__DIR__.'/../../src/Console/LaravelCrmUpgrade.php');

    $publish = strpos($source, "'--tag' => 'assets',");
    $publishFailed = strpos($source, 'Publishing Laravel CRM assets failed.');
    $prune = strpos($source, '$this->pruneStaleBuildAssets($filesystem, $target);');

    expect($publish)->not->toBeFalse()
        ->and($publishFailed)->not->toBeFalse()
        ->and($prune)->not->toBeFalse()
        ->and($prune)->toBeGreaterThan($publish)
        ->and($prune)->toBeGreaterThan($publishFailed);
});

test('a skipped publish prunes nothing', function () {
    // The other half of the same guarantee: when publishing cannot be attempted
    // the host keeps whatever bundle it has, rather than being stripped of one
    // it is still serving.
    $path = scratchPublicPath();
    $assets = $path.'/vendor/laravel-crm/assets';

    mkdir($assets, 0755, true);
    file_put_contents($assets.'/app-STALEHASH.js', '// last release');

    chmod($path.'/vendor/laravel-crm', 0555);

    try {
        $this->artisan('laravelcrm:upgrade')
            ->expectsOutputToContain('Skipping asset publishing')
            ->assertExitCode(Command::SUCCESS);

        expect(file_get_contents($assets.'/app-STALEHASH.js'))->toBe('// last release');
    } finally {
        chmod($path.'/vendor/laravel-crm', 0755);
    }
})->skip(fn () => posix_geteuid() === 0, 'root ignores the write bit');

test('upgrade leaves everything outside the build directory alone', function () {
    // img/, fonts/, libs/ and css/ are published from resources/assets and are
    // not content-hashed — a host may also have dropped its own logo in there.
    $path = scratchPublicPath();
    $root = $path.'/vendor/laravel-crm';

    mkdir($root.'/img', 0755, true);
    mkdir($root.'/fonts', 0755, true);
    mkdir($root.'/libs', 0755, true);
    mkdir($root.'/assets', 0755, true);

    file_put_contents($root.'/img/custom-logo.png', 'logo');
    file_put_contents($root.'/fonts/custom.woff2', 'font');
    file_put_contents($root.'/libs/custom.js', 'lib');

    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    expect(file_get_contents($root.'/img/custom-logo.png'))->toBe('logo')
        ->and(file_get_contents($root.'/fonts/custom.woff2'))->toBe('font')
        ->and(file_get_contents($root.'/libs/custom.js'))->toBe('lib');

});

test('upgrade leaves subdirectories of the build directory alone', function () {
    $path = scratchPublicPath();
    $nested = $path.'/vendor/laravel-crm/assets/nested';

    mkdir($nested, 0755, true);
    file_put_contents($nested.'/keep.txt', 'keep');

    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    expect(file_get_contents($nested.'/keep.txt'))->toBe('keep');

});

test('upgrade is idempotent', function () {
    $path = scratchPublicPath();
    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    $first = collect((new Filesystem)->allFiles($path))
        ->map(fn ($file) => $file->getRelativePathname())
        ->sort()
        ->values()
        ->all();

    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    $second = collect((new Filesystem)->allFiles($path))
        ->map(fn ($file) => $file->getRelativePathname())
        ->sort()
        ->values()
        ->all();

    expect($second)->toBe($first);

});

// -----------------------------------------------------------------------
// Degrading rather than failing the composer run
// -----------------------------------------------------------------------

test('upgrade succeeds with a message when there is no public directory yet', function () {
    // A build that has not created public/ is not a broken install, and failing
    // here would fail the whole `composer install`.
    expect(is_dir(public_path()))->toBeFalse();

    $this->artisan('laravelcrm:upgrade')
        ->expectsOutputToContain('Skipping asset publishing')
        ->assertExitCode(Command::SUCCESS);
});

test('upgrade publishes nothing when it decides to skip', function () {
    $this->artisan('laravelcrm:upgrade')->assertExitCode(Command::SUCCESS);

    expect(is_dir(public_path()))->toBeFalse();
});

// -----------------------------------------------------------------------
// Drifted published views
// -----------------------------------------------------------------------

/*
 * Unlike the public path, resource_path() is derived from basePath() and has no
 * setter — so these write into testbench's real resources/views/vendor/laravel-crm
 * and remove the whole tree afterwards.
 */

/** Write $contents to a published view and return its path. */
function publishView(string $relative, string $contents): string
{
    $path = resource_path('views/vendor/laravel-crm/'.$relative);

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    file_put_contents($path, $contents);

    return $path;
}

/** The package's own copy of a view, which a published copy is compared against. */
function shippedView(string $relative): string
{
    return file_get_contents(__DIR__.'/../../resources/views/'.$relative);
}

/**
 * Relative paths of $count views this package actually ships, excluding the
 * legacy PDF ones the drift check deliberately ignores.
 *
 * Read off disk rather than hardcoded so a test needing "more than ten views"
 * does not go stale the moment one of them is renamed.
 *
 * @return array<int, string>
 */
function shippedViewPaths(int $count): array
{
    $legacy = array_map(
        fn (string $view) => str_replace('.', '/', $view).'.blade.php',
        array_values(PdfTemplateRegistry::LEGACY_VIEWS)
    );

    $paths = [];

    foreach ((new Filesystem)->allFiles(__DIR__.'/../../resources/views') as $file) {
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());

        if (! str_ends_with($relative, '.blade.php') || in_array($relative, $legacy, true)) {
            continue;
        }

        $paths[] = $relative;

        if (count($paths) === $count) {
            break;
        }
    }

    return $paths;
}

afterEach(function () {
    (new Filesystem)->deleteDirectory(resource_path('views/vendor/laravel-crm'));
});

test('upgrade names a published view that has drifted from the shipped one', function () {
    // The failure this exists for: laravelcrm:upgrade runs on every deploy, and
    // nothing else tells a host that Laravel is rendering a frozen copy of a
    // view from a previous release.
    scratchPublicPath();

    publishView('livewire/users/user-index.blade.php', '<div>an old copy</div>');

    $this->artisan('laravelcrm:upgrade')
        ->expectsOutputToContain('livewire/users/user-index.blade.php')
        ->expectsOutputToContain('vendor:publish --tag=views --force')
        ->assertExitCode(Command::SUCCESS);
});

test('a byte-identical published view is not drift', function () {
    // vendor:publish --tag=views copies the entire directory, so presence says
    // nothing on its own — a host that published last week and changed nothing
    // has copies it never intended as overrides. Warning about those would
    // train operators to ignore the warning.
    scratchPublicPath();

    $relative = 'livewire/users/user-index.blade.php';
    publishView($relative, shippedView($relative));

    $this->artisan('laravelcrm:upgrade')
        ->doesntExpectOutputToContain('user-index.blade.php')
        ->assertExitCode(Command::SUCCESS);
});

test('upgrade reports a published view the package no longer ships', function () {
    // The other shape of the same problem: the component was removed, so there
    // is no counterpart to compare against and the host is carrying a view
    // nothing will ever update.
    scratchPublicPath();

    publishView('livewire/users/user-owner-filter.blade.php', '<div>removed upstream</div>');

    $this->artisan('laravelcrm:upgrade')
        // One substring, not two: each expectation is matched against a single
        // write, so two expectations on the same line only ever consume one.
        ->expectsOutputToContain('livewire/users/user-owner-filter.blade.php (this release no longer ships this view)')
        ->assertExitCode(Command::SUCCESS);
});

test('an edited legacy PDF view is a documented override, not drift', function () {
    // PdfTemplateRegistry::LEGACY_VIEWS are the views a host is explicitly meant
    // to publish and edit — probePublishedOverride() goes looking for exactly
    // this hash mismatch and honours it.
    scratchPublicPath();

    publishView('invoices/pdf.blade.php', '<div>our own invoice layout</div>');

    $this->artisan('laravelcrm:upgrade')
        ->doesntExpectOutputToContain('invoices/pdf.blade.php')
        ->assertExitCode(Command::SUCCESS);
});

test('an unreadable published view is skipped without derailing the scan', function () {
    // md5_file() on an unreadable file returns false — which compares unequal to
    // the shipped hash and reports drift that was never established — after
    // emitting a raw PHP warning into the middle of the composer output. Under
    // Pest that warning becomes an exception, so the *whole* check bails to its
    // catch-all and every other drifted view goes unreported.
    //
    // Hence the readable view alongside it: the assertion that matters is that
    // one unreadable file costs you only that file.
    scratchPublicPath();

    publishView('livewire/users/user-index.blade.php', '<div>an old copy</div>');
    $unreadable = publishView('livewire/users/user-show.blade.php', '<div>another old copy</div>');

    chmod($unreadable, 0000);

    try {
        $this->artisan('laravelcrm:upgrade')
            ->expectsOutputToContain('1 published Laravel CRM view(s) differ')
            ->expectsOutputToContain('livewire/users/user-index.blade.php')
            ->doesntExpectOutputToContain('livewire/users/user-show.blade.php')
            ->doesntExpectOutputToContain('Could not check published Laravel CRM views for drift')
            ->assertExitCode(Command::SUCCESS);
    } finally {
        chmod($unreadable, 0644);
    }
})->skip(fn () => posix_geteuid() === 0, 'root ignores the read bit');

test('the drift list is capped but the count is not', function () {
    // A host that published on an older release and never re-published has every
    // changed view drift at once. The list has to stay readable without
    // understating how much of the host is frozen on the previous release.
    scratchPublicPath();

    $relatives = shippedViewPaths(12);

    expect($relatives)->toHaveCount(12);

    foreach ($relatives as $relative) {
        publishView($relative, '<div>an old copy of '.$relative.'</div>');
    }

    // Disjoint substrings: an expectation matches every write containing it, so
    // two that could match the same line would leave the second unsatisfied.
    $this->artisan('laravelcrm:upgrade')
        ->expectsOutputToContain('12 published Laravel CRM view(s) differ')
        ->expectsOutputToContain('... and 2 more.')
        ->assertExitCode(Command::SUCCESS);
});

test('upgrade says nothing when no views have been published', function () {
    // The majority of hosts. The check has to stay silent for them.
    scratchPublicPath();

    expect(is_dir(resource_path('views/vendor/laravel-crm')))->toBeFalse();

    $this->artisan('laravelcrm:upgrade')
        ->doesntExpectOutputToContain('differ from the ones this release ships')
        ->assertExitCode(Command::SUCCESS);
});
