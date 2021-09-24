<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class LaravelCrmLabels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:labels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel CRM package';

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
        $this->info('Updating LaravelCRM Labels...');
        
        foreach (DB::table('teams')->get() as $team) {
            foreach (DB::table('labels')
                         ->whereNull('team_id')
                         ->get() as $label) {
                $this->info('Inserting label '.$label->name.' for team '.$team->name);
                
                DB::table('labels')->updateOrInsert([
                    'name' => $label->name,
                    'hex' => $label->hex,
                    'description' => $label->description,
                    'team_id' => $team->id,
                ], [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
        
        $this->info('LaravelCRM Labels Update Complete.');
    }
}
