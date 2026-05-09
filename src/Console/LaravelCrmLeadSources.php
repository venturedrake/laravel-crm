<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\LeadSource;

class LaravelCrmLeadSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:lead-sources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed default Laravel CRM lead sources';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    public function __construct(Composer $composer)
    {
        parent::__construct();
        $this->composer = $composer;
    }

    public function handle()
    {
        $this->info('Seeding default LaravelCRM lead sources...');

        $sources = [
            'Website',
            'Referral',
            'Cold Call',
            'Email Campaign',
            'Social Media',
            'Trade Show',
            'Partner',
            'Organic Search',
        ];

        $created = 0;

        foreach ($sources as $name) {
            $source = LeadSource::firstOrCreate(
                ['name' => $name],
                ['external_id' => Uuid::uuid4()->toString()]
            );

            if ($source->wasRecentlyCreated) {
                $this->info('  → Created lead source: '.$name);
                $created++;
            } else {
                $this->line('  · Lead source already exists: '.$name);
            }
        }

        $this->info("LaravelCRM Lead Sources seed complete ({$created} created).");
    }
}
