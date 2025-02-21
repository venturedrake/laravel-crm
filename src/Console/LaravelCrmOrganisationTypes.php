<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class LaravelCrmOrganizationTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:organizationtypes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Laravel CRM Organization Types';

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
        $this->info('Updating LaravelCRM Organization Types...');

        foreach (DB::table('teams')->get() as $team) {
            foreach (DB::table('organization_types')
                ->whereNull('team_id')
                ->get() as $organizationType) {
                $this->info('Inserting organization type '.$organizationType->name.' for team '.$team->name);

                $teamOrganizationType = DB::table('organization_types')->where([
                    'name' => $organizationType->name,
                    'description' => $organizationType->description,
                    'team_id' => $team->id,
                ])->first();

                if (! $teamOrganizationType) {
                    DB::table('organization_types')->insert([
                        'name' => $organizationType->name,
                        'description' => $organizationType->description,
                        'team_id' => $team->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }

        $this->info('LaravelCRM Organization Types Update Complete.');
    }
}
