<?php

namespace VentureDrake\LaravelCrm\Console;

use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Console\Command;
use VentureDrake\LaravelCrm\Models\Organisation;
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
                    foreach (Xero::contacts()->get() as $contact) {
                        $this->info('Updating LaravelCRM Xero Contact: '.$contact['Name']);
                        
                        $organisation = Organisation::select(config('laravel-crm.db_table_prefix').'organisations.*')
                            ->leftJoin(config('laravel-crm.db_table_prefix').'xero_contacts', config('laravel-crm.db_table_prefix').'organisations.id', '=', config('laravel-crm.db_table_prefix').'xero_contacts.organisation_id')
                            ->where(config('laravel-crm.db_table_prefix').'xero_contacts.contact_id', $contact['ContactID'])
                            ->first();

                        if (! $organisation) {
                            $organisation = Organisation::create([
                                'name' => $contact['Name'],
                                'user_owner_id' => \App\User::where('email', config('laravel-crm.crm_owner'))->first()->id ?? null,
                            ]);
                        } else {
                            $organisation->update([
                                'name' => $contact['Name'],
                            ]);
                        }

                        $organisation->xeroContact()->updateOrCreate([
                            'contact_id' => $contact['ContactID'],
                        ], [
                            'name' => $contact['Name'],
                        ]);
                    }

                    break;

                case "products":
                    if ($result = Xero::get('Items', $array = [])) {
                        foreach ($result['body']['Items'] as $item) {
                            $this->info('Updating LaravelCRM Xero Integration Item: '.$item['Name']);

                            $product = Product::select(config('laravel-crm.db_table_prefix').'products.*')
                                ->leftJoin(config('laravel-crm.db_table_prefix').'xero_items', config('laravel-crm.db_table_prefix').'products.id', '=', config('laravel-crm.db_table_prefix').'xero_items.product_id')
                                ->where(config('laravel-crm.db_table_prefix').'xero_items.item_id', $item['ItemID'])
                                ->first();
                            
                            if (! $product) {
                                $product = Product::create([
                                    'code' => $item['Code'],
                                    'name' => $item['Name'],
                                    'description' => $item['Description'],
                                    'user_owner_id' => \App\User::where('email', config('laravel-crm.crm_owner'))->first()->id ?? null,
                                ]);
                            } else {
                                $product->update([
                                    'code' => $item['Code'],
                                    'name' => $item['Name'],
                                    'description' => $item['Description'],
                                ]);
                            }

                            if ((isset($item['SalesDetails']['UnitPrice']))) {
                                $product->productPrices()->updateOrCreate([
                                    'currency' => \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD',
                                ], [
                                    'unit_price' => $item['SalesDetails']['UnitPrice'] * 100,
                                ]);
                            }

                            $product->xeroItem()->updateOrCreate([
                                'item_id' => $item['ItemID'],
                            ], [
                                'code' => $item['Code'],
                                'name' => $item['Name'],
                                'inventory_tracked' => $item['IsTrackedAsInventory'],
                                'is_sold' => $item['IsSold'],
                                'is_purchased' => $item['IsPurchased'],
                                'purchase_price' => (isset($item['PurchaseDetails']['UnitPrice'])) ? $item['PurchaseDetails']['UnitPrice'] * 100 : null,
                                'sell_price' => (isset($item['SalesDetails']['UnitPrice'])) ? $item['SalesDetails']['UnitPrice'] * 100 : null,
                                'purchase_description' => $item['PurchaseDescription'],
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
        } else {
            $this->error('LaravelCRM Xero integration not connected');
        }
        
        $this->info('Updating LaravelCRM Xero Integration Complete.');
    }
}
