<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class LaravelCrmOrganisationTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:organisationtypes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Laravel CRM Organisation Types';

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
        $this->info('Updating LaravelCRM Organisation Types...');

        foreach (DB::table('teams')->get() as $team) {
            foreach (DB::table('organisation_types')
                         ->whereNull('team_id')
                         ->get() as $organisationType) {
                $this->info('Inserting organisation type '.$organisationType->name.' for team '.$team->name);

                $teamOrganisationType = DB::table('organisation_types')->where([
                    'name' => $organisationType->name,
                    'description' => $organisationType->description,
                    'team_id' => $team->id,
                ])->first();

                if (! $teamOrganisationType) {
                    DB::table('organisation_types')->insert([
                        'name' => $organisationType->name,
                        'description' => $organisationType->description,
                        'team_id' => $team->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }

        $this->info('LaravelCRM Organisation Types Update Complete.');
    }
}
