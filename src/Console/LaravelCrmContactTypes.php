<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class LaravelCrmContactTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:contacttypes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Laravel CRM Contact Types';

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
        $this->info('Updating LaravelCRM Contact Types...');

        foreach (DB::table('teams')->get() as $team) {
            foreach (DB::table('contact_types')
                         ->whereNull('team_id')
                         ->get() as $contactType) {
                $this->info('Inserting contact type '.$contactType->name.' for team '.$team->name);

                $teamContactType = DB::table('contact_types')->where([
                    'name' => $contactType->name,
                    'description' => $contactType->description,
                    'team_id' => $team->id,
                ])->first();

                if (! $teamContactType) {
                    DB::table('contact_types')->insert([
                        'name' => $contactType->name,
                        'description' => $contactType->description,
                        'team_id' => $team->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }

        $this->info('LaravelCRM Contact Types Update Complete.');
    }
}
