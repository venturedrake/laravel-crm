<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

/**
 * The half of the update that is safe to run unattended.
 *
 * This is what the host's `post-autoload-dump` composer hook fires, so it runs
 * on every `composer install` and `composer update` — including on a production
 * box mid-deploy, as whatever user the build runs as, with no TTY. That places
 * three hard constraints on everything below:
 *
 *  - It never touches the database. No migrate, no seeders, not even a Setting
 *    read: during a build the database may be unreachable, mid-migration, or
 *    belong to a different release. Database work lives in laravelcrm:update,
 *    which an operator runs explicitly.
 *  - It never prompts. There is nobody to answer.
 *  - It exits SUCCESS when the application is not in a state to publish into,
 *    rather than failing the composer run. A build that has not created
 *    public/ yet is not a broken install; only a publish that is attempted and
 *    fails is.
 */
class LaravelCrmUpgrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Republish Laravel CRM assets and clear caches. Safe to run on every composer install — touches no database';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem): int
    {
        $this->info('Upgrading Laravel CRM...');

        $target = public_path('vendor/laravel-crm');

        if ($problem = $this->publishBlockedBecause($filesystem, $target)) {
            $this->info("Skipping asset publishing: {$problem}.");
            $this->info('Run "php artisan laravelcrm:upgrade" once the application is set up.');

            return self::SUCCESS;
        }

        $this->info('Publishing assets...');

        try {
            $exitCode = $this->call('vendor:publish', [
                '--provider' => 'VentureDrake\LaravelCrm\LaravelCrmServiceProvider',
                '--tag' => 'assets',
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            $this->error('Could not publish Laravel CRM assets: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($exitCode !== self::SUCCESS) {
            $this->error('Publishing Laravel CRM assets failed.');

            return self::FAILURE;
        }

        // After the publish, never before. The bundle is content-hashed, so on
        // a host coming from an older release every file in assets/ is stale
        // and pruning first would empty the directory — leaving a host whose
        // publish then failed with no JS or CSS at all, where it would
        // otherwise still be serving the previous release's working bundle.
        $this->pruneStaleBuildAssets($filesystem, $target);

        $this->info('Publishing Flasher assets...');

        // Not fatal: flash notifications degrade, the CRM still renders.
        try {
            $this->callSilent('flasher:install');
        } catch (\Throwable $e) {
            $this->warn('Could not publish Flasher assets: '.$e->getMessage());
            $this->warn('Run "php artisan flasher:install" manually if flash notifications are not working.');
        }

        $this->info('Clearing cached config, routes and views...');

        // config:clear matters most of the three: new config keys arrive via
        // mergeConfigFrom, and a stale config:cache from the previous release
        // hides every one of them.
        foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
            try {
                $this->callSilent($command);
            } catch (\Throwable $e) {
                $this->warn("Could not run {$command}: ".$e->getMessage());
            }
        }

        $this->warnAboutDriftedPublishedViews($filesystem);

        $this->info('Laravel CRM assets are up to date.');
        $this->line('Run "php artisan laravelcrm:update" to apply database migrations.');

        return self::SUCCESS;
    }

    /**
     * Tell the operator which published views no longer match the ones shipped.
     *
     * A view published into resources/views/vendor/laravel-crm is a frozen copy
     * that Laravel resolves *ahead of* the package's own. Nothing re-syncs it,
     * so when a Livewire component stops passing a variable the frozen view
     * still references it and the page 500s — with no hint that the host is
     * rendering a view from a previous release.
     *
     * Only a hash mismatch counts. vendor:publish --tag=views copies the whole
     * directory, so presence says nothing on its own: a host that published
     * last week and changed nothing has byte-identical copies it never intended
     * as overrides, and warning about those would train operators to ignore
     * this. Same reasoning as PdfTemplateRegistry::probePublishedOverride().
     *
     * Never fatal, and never noisy on the majority of hosts, which have not
     * published views at all.
     */
    protected function warnAboutDriftedPublishedViews(Filesystem $filesystem): void
    {
        try {
            $published = resource_path('views/vendor/laravel-crm');

            if (! $filesystem->isDirectory($published) || ! is_readable($published)) {
                return;
            }

            $shippedRoot = __DIR__.'/../../resources/views';

            // The PDF views a host is explicitly meant to publish and edit. An
            // edited copy of one of these is a supported customisation that
            // PdfTemplateRegistry goes looking for, not drift.
            $supported = array_map(
                fn (string $view) => str_replace('.', '/', $view).'.blade.php',
                array_values(PdfTemplateRegistry::LEGACY_VIEWS)
            );

            $drifted = [];

            foreach ($filesystem->allFiles($published) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $relative = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());

                if (in_array($relative, $supported, true)) {
                    continue;
                }

                $shipped = $shippedRoot.'/'.$relative;

                if (! is_file($shipped)) {
                    $drifted[] = $relative.' (this release no longer ships this view)';

                    continue;
                }

                // A file we cannot hash tells us nothing either way, and
                // md5_file() on it would emit a raw PHP warning into the middle
                // of the composer output and then return false — which compares
                // unequal to the shipped hash and reports drift we never
                // established. Skip it, as probePublishedOverride() does.
                if (! is_readable($file->getPathname())) {
                    continue;
                }

                if (md5_file($file->getPathname()) !== md5_file($shipped)) {
                    $drifted[] = $relative;
                }
            }

            if ($drifted === []) {
                return;
            }

            $shown = array_slice($drifted, 0, 10);

            $this->warn(count($drifted).' published Laravel CRM view(s) differ from the ones this release ships:');

            foreach ($shown as $path) {
                $this->warn('  - '.$path);
            }

            if ($remaining = count($drifted) - count($shown)) {
                $this->warn("  ... and {$remaining} more.");
            }

            $this->warn('A published view is a frozen copy and is rendered instead of the package\'s own, so when a component stops passing a variable the frozen view still references it and the page 500s.');
            $this->warn('Run "php artisan vendor:publish --tag=views --force" to take this release\'s views, then re-apply any customisations on top of them.');
        } catch (\Throwable $e) {
            // An unreadable resources directory is not a broken install.
            $this->warn('Could not check published Laravel CRM views for drift: '.$e->getMessage());
        }
    }

    /**
     * Why publishing cannot be attempted, or null when it can.
     */
    protected function publishBlockedBecause(Filesystem $filesystem, string $target): ?string
    {
        $public = public_path();

        if (! $filesystem->isDirectory($public)) {
            return "the application has no public directory at {$public}";
        }

        // vendor/laravel-crm may not exist yet — the publish creates it. What
        // has to be writable is the deepest directory that does exist.
        $probe = $target;

        while ($probe !== $public && ! $filesystem->isDirectory($probe)) {
            $parent = dirname($probe);

            if ($parent === $probe) {
                break;
            }

            $probe = $parent;
        }

        if (! $filesystem->isWritable($probe)) {
            return "{$probe} is not writable";
        }

        return null;
    }

    /**
     * Delete build output the host has that this package no longer ships.
     *
     * public/vendor/laravel-crm/assets holds the Vite bundle under
     * content-hashed filenames. Publishing with --force overwrites and adds but
     * never removes, so a host accumulates one app-<hash>.js per release it has
     * ever installed while manifest.json names exactly one of them.
     *
     * Runs only after the publish has been confirmed to have succeeded, so the
     * files it deletes have already been replaced. Doing it the other way round
     * would open a window in which the host has neither bundle.
     *
     * Only top-level files are considered, and only ones with no counterpart in
     * the package. Everything published from resources/assets — img/, fonts/,
     * libs/, css/ — lives outside this directory and is never touched.
     */
    protected function pruneStaleBuildAssets(Filesystem $filesystem, string $target): void
    {
        $source = __DIR__.'/../../public/vendor/laravel-crm/assets';
        $destination = $target.DIRECTORY_SEPARATOR.'assets';

        // No source bundle means nothing to replace what we would delete, so
        // leave the host's copy in place.
        if (! $filesystem->isDirectory($source) || ! $filesystem->isDirectory($destination)) {
            return;
        }

        $current = [];

        foreach ($filesystem->files($source) as $file) {
            $current[$file->getFilename()] = true;
        }

        $removed = 0;

        foreach ($filesystem->files($destination) as $file) {
            if (isset($current[$file->getFilename()])) {
                continue;
            }

            $filesystem->delete($file->getPathname());
            $removed++;
        }

        if ($removed > 0) {
            $this->info("Removed {$removed} stale build asset(s) from {$destination}.");
        }
    }
}
