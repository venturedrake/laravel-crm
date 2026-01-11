<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings;

use Livewire\Component;

class SettingEdit extends Component
{
    public array $countries = [];

    public array $languages = [
        [
            'id' => 'english',
            'name' => 'English',
        ],
    ];

    public array $currencies = [];

    public array $timezones = [];

    public array $dateFormats = [];

    public array $timeFormats = [];

    public $organizationName;

    public $vatNumber;

    public $language;

    public $country;

    public $currency;

    public $timezone;

    public $logoFile;

    public $leadPrefix;

    public $dealPrefix;

    public $quotePrefix;

    public $orderPrefix;

    public $invoicePrefix;

    public $deliveryPrefix;

    public $purchaseOrderPrefix;

    public $quoteTerms;

    public $invoiceContactDetails;

    public $invoiceTerms;

    public $invoicePaymentInstructions;

    public $purchaseOrderTerms;

    public $purchaseOrderDeliveryInstructions;

    public $dateFormat;

    public $timeFormat;

    public $showRelatedActivity;

    public $dynamicProducts;

    public $taxName;

    public $taxRate;

    public $related;

    public function mount()
    {
        $this->countries = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries();

        foreach (\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies() as $id => $value) {
            $this->currencies[] = [
                'id' => $id,
                'name' => $value,
            ];
        }

        foreach (\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timezones() as $id => $value) {
            $this->timezones[] = [
                'id' => $id,
                'name' => $value,
            ];
        }

        foreach (\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\dateFormats() as $id => $value) {
            $this->dateFormats[] = [
                'id' => $id,
                'name' => $value,
            ];
        }

        foreach (\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timeFormats() as $id => $value) {
            $this->timeFormats[] = [
                'id' => $id,
                'name' => $value,
            ];
        }

        $this->organizationName = app('laravel-crm.settings')->get('organization_name');
        $this->vatNumber = app('laravel-crm.settings')->get('vat_number');
        $this->language = app('laravel-crm.settings')->get('language', 'english');
        $this->country = app('laravel-crm.settings')->get('country', 'United States');
        $this->currency = app('laravel-crm.settings')->get('currency', 'USD');
        $this->timezone = app('laravel-crm.settings')->get('timezone');
        $this->logoFile = app('laravel-crm.settings')->get('logo_file');
        $this->leadPrefix = app('laravel-crm.settings')->get('lead_prefix');
        $this->dealPrefix = app('laravel-crm.settings')->get('deal_prefix');
        $this->quotePrefix = app('laravel-crm.settings')->get('quote_prefix');
        $this->orderPrefix = app('laravel-crm.settings')->get('order_prefix');
        $this->invoicePrefix = app('laravel-crm.settings')->get('invoice_prefix');
        $this->deliveryPrefix = app('laravel-crm.settings')->get('delivery_prefix');
        $this->purchaseOrderPrefix = app('laravel-crm.settings')->get('purchase_order_prefix');
        $this->quoteTerms = app('laravel-crm.settings')->get('quote_terms');
        $this->invoiceContactDetails = app('laravel-crm.settings')->get('invoice_contact_details');
        $this->invoiceTerms = app('laravel-crm.settings')->get('invoice_terms');
        $this->invoicePaymentInstructions = app('laravel-crm.settings')->get('invoice_payment_instructions');
        $this->purchaseOrderTerms = app('laravel-crm.settings')->get('purchase_order_terms');
        $this->purchaseOrderDeliveryInstructions = app('laravel-crm.settings')->get('purchase_order_delivery_instructions');
        $this->dateFormat = app('laravel-crm.settings')->get('date_format');
        $this->timeFormat = app('laravel-crm.settings')->get('time_format');
        $this->showRelatedActivity = app('laravel-crm.settings')->get('show_related_activity');
        $this->dynamicProducts = app('laravel-crm.settings')->get('dynamic_products');
        $this->taxName = app('laravel-crm.settings')->get('tax_name');
        $this->taxRate = app('laravel-crm.settings')->get('tax_rate');
        $this->related = app('laravel-crm.settings')->get('team');
    }

    public function save()
    {
        $this->validate();

        $this->label->update([
            'name' => $this->name,
            'description' => $this->description,
            'hex' => $this->hex ?? '000000',
        ]);

        $this->success(
            ucfirst(trans('laravel-crm::lang.label_updated')),
            redirectTo: route('laravel-crm.labels.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.setting-edit');
    }
}
