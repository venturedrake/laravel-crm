<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\SettingService;

class LaravelCrmUpdate extends Command
{
    /**
     * @var SettingService
     */
    private $settingService;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:update';

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
    public function __construct(Composer $composer, SettingService $settingService)
    {
        parent::__construct();
        $this->composer = $composer;
        $this->settingService = $settingService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating Laravel CRM...');

        $this->info('Updating Laravel CRM quote numbers...');
        
        foreach (Quote::whereNull('number')->get() as $quote) {
            $this->info('Updating Laravel CRM quote #'.$quote->id);
            
            $quote->update([
                'quote_id' => $this->settingService->get('quote_prefix')->value.(1000 + $quote->id),
                'prefix' => $this->settingService->get('quote_prefix')->value,
                'number' => 1000 + $quote->id,
            ]);
        }

        $this->info('Updating Laravel CRM quote numbers complete');

        $this->info('Updating Laravel CRM order numbers...');

        foreach (Order::whereNull('number')->get() as $order) {
            $this->info('Updating Laravel CRM order #'.$order->id);

            $order->update([
                'order_id' => $this->settingService->get('order_prefix')->value.(1000 + $order->id),
                'prefix' => $this->settingService->get('order_prefix')->value,
                'number' => 1000 + $order->id,
            ]);
        }

        $this->info('Updating Laravel CRM orders numbers complete');

        $this->info('Laravel CRM is now updated.');
    }
}
