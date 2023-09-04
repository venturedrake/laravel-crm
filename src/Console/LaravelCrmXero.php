<?php

namespace VentureDrake\LaravelCrm\Console;

use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Console\Command;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Setting;

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
                    if (Setting::where('name', 'xero_contacts')->first() && Setting::where('name', 'xero_contacts')->first()->value == 1) {
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

                            if (isset($contact['FirstName']) || isset($contact['LastName'])) {
                                $person = Person::select(config('laravel-crm.db_table_prefix').'people.*')
                                    ->leftJoin(config('laravel-crm.db_table_prefix').'xero_people', config('laravel-crm.db_table_prefix').'people.id', '=', config('laravel-crm.db_table_prefix').'xero_people.person_id')
                                    ->where(config('laravel-crm.db_table_prefix').'xero_people.contact_id', $contact['ContactID'])
                                    ->first();

                                if (! $person) {
                                    $person = Person::create([
                                        'first_name' => $contact['FirstName'] ?? null,
                                        'last_name' => $contact['LastName'] ?? null,
                                        'user_owner_id' => \App\User::where('email', config('laravel-crm.crm_owner'))->first()->id ?? null,
                                        'organisation_id' => $organisation->id,
                                    ]);
                                } else {
                                    $person->update([
                                        'first_name' => $contact['FirstName'] ?? null,
                                        'last_name' => $contact['LastName'] ?? null,
                                        'organisation_id' => $organisation->id,
                                    ]);
                                }

                                if (isset($contact['EmailAddress'])) {
                                    $person->emails()->updateOrCreate([
                                        'primary' => 1,
                                    ], [
                                        'address' => $contact['EmailAddress'],
                                        'type' => 'work',
                                    ]);
                                }

                                $person->xeroPerson()->updateOrCreate([
                                    'contact_id' => $contact['ContactID'],
                                ], [
                                    'first_name' => $contact['FirstName'] ?? null,
                                    'last_name' => $contact['LastName'] ?? null,
                                    'email' => $contact['EmailAddress'] ?? null,
                                    'is_primary' => 1,
                                ]);
                            }
                        }
                    } else {
                        $this->info('LaravelCRM Xero Integration '.ucfirst($this->argument('model')).' disabled');
                    }

                    break;

                case "products":
                    if (Setting::where('name', 'xero_products')->first() && Setting::where('name', 'xero_products')->first()->value == 1) {
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
                                        'purchase_account' => (isset($item['PurchaseDetails']['AccountCode'])) ? $item['PurchaseDetails']['AccountCode'] : null,
                                        'sales_account' => (isset($item['SalesDetails']['AccountCode'])) ? $item['SalesDetails']['AccountCode'] : null,
                                        'tax_rate' => $this->taxTypePercentage($item),
                                        'name' => $item['Name'],
                                        'description' => $item['Description'] ?? null,
                                        'user_owner_id' => \App\User::where('email', config('laravel-crm.crm_owner'))->first()->id ?? null,
                                    ]);
                                } else {
                                    $product->update([
                                        'code' => $item['Code'],
                                        'purchase_account' => (isset($item['PurchaseDetails']['AccountCode'])) ? $item['PurchaseDetails']['AccountCode'] : null,
                                        'sales_account' => (isset($item['SalesDetails']['AccountCode'])) ? $item['SalesDetails']['AccountCode'] : null,
                                        'tax_rate' => $this->taxTypePercentage($item),
                                        'name' => $item['Name'],
                                        'description' => $item['Description'] ?? null,
                                    ]);
                                }

                                if ((isset($item['SalesDetails']['UnitPrice']))) {
                                    $product->productPrices()->updateOrCreate([
                                        'currency' => \VentureDrake\LaravelCrm\Models\Setting::currency()->value ?? 'USD',
                                    ], [
                                        'unit_price' => $item['SalesDetails']['UnitPrice'],
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
                                    'purchase_price' => (isset($item['PurchaseDetails']['UnitPrice'])) ? $item['PurchaseDetails']['UnitPrice'] : null,
                                    'sell_price' => (isset($item['SalesDetails']['UnitPrice'])) ? $item['SalesDetails']['UnitPrice'] : null,
                                    'purchase_description' => $item['PurchaseDescription'] ?? null,
                                ]);
                            }
                        }
                    } else {
                        $this->info('LaravelCRM Xero Integration '.ucfirst($this->argument('model')).' disabled');
                    }

                    foreach (Product::all() as $product) {
                        if (! $product->xeroItem && $product->code) {
                            $this->info('LaravelCRM Xero Integration Adding product '.$product->name.' to Xero');

                            $xeroProduct = Xero::post('Items', [
                                'Code' => $product->code,
                                'Name' => $product->name,
                                'Description' => $product->description,
                                'PurchaseDetails' => [
                                    'AccountCode' => $product->purchase_account ?? 310,
                                ],
                                'SalesDetails' => [
                                    'UnitPrice' => ($product->getDefaultPrice()->unit_price) ? $product->getDefaultPrice()->unit_price / 100 : null,
                                    'AccountCode' => $product->sales_account ?? 200,
                                ],
                            ]);

                            $item = $xeroProduct['body']['Items'][0];

                            $product->xeroItem()->updateOrCreate([
                                'item_id' => $item['ItemID'],
                            ], [
                                'code' => $item['Code'],
                                'name' => $item['Name'],
                                'inventory_tracked' => $item['IsTrackedAsInventory'],
                                'is_sold' => $item['IsSold'],
                                'is_purchased' => $item['IsPurchased'],
                                'purchase_price' => (isset($item['PurchaseDetails']['UnitPrice'])) ? $item['PurchaseDetails']['UnitPrice'] : null,
                                'sell_price' => (isset($item['SalesDetails']['UnitPrice'])) ? $item['SalesDetails']['UnitPrice'] : null,
                                'purchase_description' => $item['PurchaseDescription'] ?? null,
                            ]);
                        }
                    }

                    break;

                case "quotes":
                    //
                    break;

                case "invoices":
                    if (Setting::where('name', 'xero_invoices')->first() && Setting::where('name', 'xero_invoices')->first()->value == 1) {
                        foreach (Xero::invoices()->get() as $invoice) {
                            // Update invoices TBC
                        }
                    }

                    break;
            }
        } else {
            $this->error('LaravelCRM Xero integration not connected');
        }

        $this->info('Updating LaravelCRM Xero Integration Complete.');
    }

    protected function taxTypePercentage($item)
    {
        if (isset($item['SalesDetails']['TaxType'])) {
            switch ($item['SalesDetails']['TaxType']) {
                case "OUTPUT":
                    return 10;

                    break;

                case "INPUT":
                    return 0;

                    break;

                case "INPUT2":
                    return 15;

                    break;

                case "CAPEXOUTPUT":
                    return 17.5;

                    break;
            }
        }

        return null;
    }
}
