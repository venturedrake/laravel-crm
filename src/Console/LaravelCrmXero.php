<?php

namespace VentureDrake\LaravelCrm\Console;

use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Console\Command;
use VentureDrake\LaravelCrm\Models\Product;

class LaravelCrmXero extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:xero {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Updating LaravelCRM Xero Integration '.ucfirst($this->argument('model')).'...');

        if (Xero::isConnected()) {
            $tenantName = Xero::getTenantName();

            switch ($this->argument('model')) {
                case "contacts":
                    dd(Xero::contacts()->get());
                    break;

                case "products":
                    if($result = Xero::get('Items', $array = [])){
                        foreach($result['body']['Items'] as $item){
                            $this->info('Updating LaravelCRM Xero Integration Item: '.$item['Name']);
                            
                            Product::updateOrCreate([
                                
                            ],[
                                
                            ]);
                        }
                    }
                    
                    break;

                case "quotes":
                    //
                    break;

                case "invoices":
                    //
                    break;
            }
        }else{
            $this->error('LaravelCRM Xero integration not connected');
        }
        
        $this->info('Updating LaravelCRM Xero Integration Complete.');
    }
}
