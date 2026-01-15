<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class SettingEdit extends Component
{
    use Toast;
    use WithFileUploads;

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

    public $logo;

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

    protected function rules()
    {
        return [
            'organizationName' => 'required|max:255',
            'country' => 'required',
            'language' => 'required',
            'currency' => 'required',
            'timezone' => 'required',
            'dateFormat' => 'required',
            'timeFormat' => 'required',
            'logoFile' => 'nullable|image|max:1024',
        ];
    }

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
        $this->logo = app('laravel-crm.settings')->get('logo_file');
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
        $this->showRelatedActivity = (app('laravel-crm.settings')->get('show_related_activity')) ? true : false;
        $this->dynamicProducts = (app('laravel-crm.settings')->get('dynamic_products')) ? true : false;
        $this->taxName = app('laravel-crm.settings')->get('tax_name');
        $this->taxRate = app('laravel-crm.settings')->get('tax_rate');
        $this->related = app('laravel-crm.settings')->get('team');
    }

    public function save()
    {
        $this->validate();

        app('laravel-crm.settings')->set('organization_name', $this->organizationName);

        if ($this->vatNumber) {
            app('laravel-crm.settings')->set('vat_number', $this->vatNumber);
        }

        app('laravel-crm.settings')->set('language', $this->language);
        app('laravel-crm.settings')->set('country', $this->country);
        app('laravel-crm.settings')->set('currency', $this->currency);
        app('laravel-crm.settings')->set('timezone', $this->timezone);

        if ($this->taxName) {
            app('laravel-crm.settings')->set('tax_name', $this->taxName);
        }

        if ($this->taxRate) {
            app('laravel-crm.settings')->set('tax_rate', $this->taxRate);
        }

        if ($this->leadPrefix) {
            app('laravel-crm.settings')->set('lead_prefix', $this->leadPrefix);
        }

        if ($this->dealPrefix) {
            app('laravel-crm.settings')->set('deal_prefix', $this->dealPrefix);
        }

        if ($this->quotePrefix) {
            app('laravel-crm.settings')->set('quote_prefix', $this->quotePrefix);
        }

        if ($this->orderPrefix) {
            app('laravel-crm.settings')->set('order_prefix', $this->orderPrefix);
        }

        if ($this->invoicePrefix) {
            app('laravel-crm.settings')->set('invoice_prefix', $this->invoicePrefix);
        }

        if ($this->deliveryPrefix) {
            app('laravel-crm.settings')->set('delivery_prefix', $this->deliveryPrefix);
        }

        if ($this->purchaseOrderPrefix) {
            app('laravel-crm.settings')->set('purchase_order_prefix', $this->purchaseOrderPrefix);
        }

        if ($this->quoteTerms) {
            app('laravel-crm.settings')->set('quote_terms', $this->quoteTerms);
        }

        if ($this->invoiceContactDetails) {
            app('laravel-crm.settings')->set('invoice_contact_details', $this->invoiceContactDetails);
        }

        if ($this->invoiceTerms) {
            app('laravel-crm.settings')->set('invoice_terms', $this->invoiceTerms);
        }

        if ($this->invoicePaymentInstructions) {
            app('laravel-crm.settings')->set('invoice_payment_instructions', $this->invoicePaymentInstructions);
        }

        if ($this->purchaseOrderTerms) {
            app('laravel-crm.settings')->set('purchase_order_terms', $this->purchaseOrderTerms);
        }

        if ($this->purchaseOrderDeliveryInstructions) {
            app('laravel-crm.settings')->set('purchase_order_delivery_instructions', $this->purchaseOrderDeliveryInstructions);
        }

        app('laravel-crm.settings')->set('date_format', $this->dateFormat);
        app('laravel-crm.settings')->set('time_format', $this->timeFormat);

        if ($file = $this->logoFile) {
            if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
                $filePath = 'laravel-crm/'.auth()->user()->currentTeam->id;
            } else {
                $filePath = 'laravel-crm';
            }

            // $file->move(storage_path('app/public/'.$filePath), $file->getClientOriginalName());
            $file->storePubliclyAs(path: $filePath, name: $file->getClientOriginalName(), options: 'public');
            app('laravel-crm.settings')->set('logo_file', $filePath.'/'.$file->getClientOriginalName());
            app('laravel-crm.settings')->set('logo_file_name', $file->getClientOriginalName());
        }

        if ($this->organizationName && config('laravel-crm.teams') && auth()->user()->currentTeam) {
            DB::table('teams')
                ->where('id', auth()->user()->currentTeam->id)
                ->update(['name' => $this->organizationName]);
        }

        app('laravel-crm.settings')->set('dynamic_products', $this->dynamicProducts);
        app('laravel-crm.settings')->set('show_related_activity', $this->showRelatedActivity);

        $related = app('laravel-crm.settings')->get('team');

        // TODO:: related
        /*$this->updateRelatedPhones($related, $this->phones);
        $this->updateRelatedEmails($related, $this->emails);
        $this->updateRelatedAddresses($related, $this->addresses);*/

        $this->success(
            ucfirst(trans('laravel-crm::lang.settings_updated'))
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.setting-edit');
    }
}
