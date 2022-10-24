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
                    // Xero::contacts()->get();
                    break;

                case "products":
                    if ($result = Xero::get('Items', $array = [])) {
                        foreach ($result['body']['Items'] as $item) {
                            $this->info('Updating LaravelCRM Xero Integration Item: '.$item['Name']);
                            
                            if ($product = Product::select(config('laravel-crm.db_table_prefix').'products.*')
                                ->leftJoin(config('laravel-crm.db_table_prefix').'xero_items', config('laravel-crm.db_table_prefix').'products.id', '=', config('laravel-crm.db_table_prefix').'xero_items.product_id')
                                ->where(config('laravel-crm.db_table_prefix').'xero_items.item_id', $item['ItemID'])
                                ->first()) {
                                $product->update([
                                    'code' => $item['Code'],
                                    'name' => $item['Name'],
                                ]);
                                
                                $product->xeroItem->update([
                                    'code' => $item['Code'],
                                    'name' => $item['Name'],
                                ]);
                            } else {
                                $product = Product::create([
                                    'code' => $item['Code'],
                                    'name' => $item['Name'],
                                    'user_owner_id' => \App\User::where('email', config('laravel-crm.crm_owner'))->first()->id ?? null,
                                ]);
                                
                                $product->xeroItem()->create([
                                    'item_id' => $item['ItemID'],
                                    'code' => $item['Code'],
                                    'name' => $item['Name'],
                                ]);
                            }
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
        } else {
            $this->error('LaravelCRM Xero integration not connected');
        }
        
        $this->info('Updating LaravelCRM Xero Integration Complete.');
    }
}
