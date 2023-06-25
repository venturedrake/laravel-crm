<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class LaravelCrmAddressTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:addresstypes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Laravel CRM address types';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating LaravelCRM Address Types...');

        foreach (DB::table('teams')->get() as $team) {
            foreach (DB::table('address_types')
                         ->whereNull('team_id')
                         ->get() as $addressType) {
                $this->info('Inserting address type '.$addressType->name.' for team '.$team->name);

                $teamAddressType = DB::table('address_types')->where([
                    'name' => $addressType->name,
                    'description' => $addressType->description,
                    'team_id' => $team->id,
                ])->first();

                if (! $teamAddressType) {
                    DB::table('address_types')->insert([
                        'name' => $addressType->name,
                        'description' => $addressType->description,
                        'team_id' => $team->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }

        $this->info('LaravelCRM Address Types Update Complete.');
    }
}
