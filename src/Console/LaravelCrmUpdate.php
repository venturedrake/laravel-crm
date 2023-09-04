<?php

namespace VentureDrake\LaravelCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
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

        if($this->settingService->get('db_update_0180')->value == 0) {
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

            $this->settingService->set('db_update_0180', 1);
            $this->info('Updating Laravel CRM orders numbers complete');
        }

        if($this->settingService->get('db_update_0181')->value == 0) {
            $this->info('Updating Laravel CRM organisation linked to person...');

            foreach (Person::whereNotNull('organisation_id')->get() as $person) {
                if($contact = $person->contacts()->create([
                    'team_id' => $person->team_id,
                    'entityable_type' => $person->organisation->getMorphClass(),
                    'entityable_id' => $person->organisation->id,
                ])) {
                    $person->update([
                        'organisation_id' => null,
                    ]);
                }
            }

            $this->settingService->set('db_update_0181', 1);
            $this->info('Updating Laravel CRM organisation linked to person complete.');
        }

        if($this->settingService->get('db_update_0191')->value == 0) {
            $this->info('Updating Laravel CRM split orders, invoices & deliveries...');

            foreach(Order::whereNotNull('quote_id')->get() as $order) {
                if($order->quote) {
                    foreach($order->quote->quoteProducts as $quoteProduct) {
                        if($orderProduct = $order->orderProducts()
                            ->whereNull('quote_product_id')
                            ->where([
                                'product_id' => $quoteProduct->product_id,
                                'price' => $quoteProduct->price,
                            ])->first()) {
                            $orderProduct->update([
                                'quote_product_id' => $quoteProduct->id
                            ]);
                        }
                    }
                }
            }

            foreach(Invoice::whereNotNull('order_id')->get() as $invoice) {
                if($invoice->order) {
                    foreach($invoice->order->orderProducts as $orderProduct) {
                        if($invoiceLine = $invoice->invoiceLines()
                            ->whereNull('order_product_id')
                            ->where([
                                'product_id' => $orderProduct->product_id,
                                'price' => $orderProduct->price,
                            ])->first()) {
                            $invoiceLine->update([
                                'order_product_id' => $orderProduct->id
                            ]);
                        }
                    }
                }
            }

            $this->settingService->set('db_update_0191', 1);
            $this->info('Updating Laravel CRM split orders, invoices & deliveries complete.');
        }

        if($this->settingService->get('db_update_0193')->value == 0) {
            $this->info('Updating Laravel CRM split deliveries...');

            foreach(Delivery::whereNotNull('order_id')->get() as $delivery) {
                if($delivery->order) {
                    foreach($delivery->order->orderProducts as $orderProduct) {
                        if($deliveryProduct = $delivery->deliveryProducts()
                            ->whereNull('quantity')
                            ->where([
                                'order_product_id' => $orderProduct->id,
                            ])->first()) {
                            $deliveryProduct->update([
                                'quantity' => $orderProduct->quantity
                            ]);
                        }
                    }
                }
            }

            $this->settingService->set('db_update_0193', 1);
            $this->info('Updating Laravel CRM split deliveries complete.');
        }

        if($this->settingService->get('db_update_0194')->value == 0) {
            $this->info('Updating Laravel CRM delivery numbers...');

            foreach (Delivery::whereNull('number')->get() as $delivery) {
                $this->info('Updating Laravel CRM delivery #'.$delivery->id);

                $delivery->update([
                    'delivery_id' => $this->settingService->get('delivery_prefix')->value.(1000 + $delivery->id),
                    'prefix' => $this->settingService->get('delivery_prefix')->value,
                    'number' => 1000 + $delivery->id,
                ]);
            }

            $this->settingService->set('db_update_0194', 1);
            $this->info('Updating Laravel CRM delivery numbers complete');
        }

        if($this->settingService->get('db_update_0199')->value == 0) {
            $this->info('Updating Laravel CRM tax amounts...');

            foreach (QuoteProduct::whereNull('tax_amount')->get() as $quoteProduct) {
                $this->info('Updating Laravel CRM quote product tax #'.$quoteProduct->id);

                if($quoteProduct->product && $quoteProduct->product->taxRate) {
                    $taxRate = $quoteProduct->product->taxRate->rate;
                } elseif($quoteProduct->product && $quoteProduct->product->tax_rate) {
                    $taxRate = $quoteProduct->product->tax_rate;
                } else {
                    $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                }

                $quoteProduct->update([
                    'tax_rate' => $taxRate,
                    'tax_amount' => $quoteProduct->amount * ($taxRate / 100)
                ]);
            }

            foreach (OrderProduct::whereNull('tax_amount')->get() as $orderProduct) {
                $this->info('Updating Laravel CRM order product tax #'.$orderProduct->id);

                if($orderProduct->product && $orderProduct->product->taxRate) {
                    $taxRate = $orderProduct->product->taxRate->rate;
                } elseif($orderProduct->product && $orderProduct->product->tax_rate) {
                    $taxRate = $orderProduct->product->tax_rate;
                } else {
                    $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                }

                $orderProduct->update([
                    'tax_rate' => $taxRate,
                    'tax_amount' => $orderProduct->amount * ($taxRate / 100)
                ]);
            }

            foreach (InvoiceLine::whereNull('tax_amount')->get() as $invoiceLine) {
                $this->info('Updating Laravel CRM invoice line tax #'.$invoiceLine->id);

                if($invoiceLine->product && $invoiceLine->product->taxRate) {
                    $taxRate = $invoiceLine->product->taxRate->rate;
                } elseif($invoiceLine->product && $invoiceLine->product->tax_rate) {
                    $taxRate = $invoiceLine->product->tax_rate;
                } else {
                    $taxRate = Setting::where('name', 'tax_rate')->first()->value ?? 0;
                }

                $invoiceLine->update([
                    'tax_rate' => $taxRate,
                    'tax_amount' => ($invoiceLine->amount * ($taxRate / 100)) / 100
                ]);
            }

            $this->settingService->set('db_update_0199', 1);
            $this->info('Updating Laravel CRM tax amounts complete');
        }

        $this->info('Laravel CRM is now updated.');
    }
}
