<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;

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

    public array $phoneTypes = [];

    public array $phones = [];

    public array $emailTypes = [];

    public array $emails = [];

    public array $addressTypes = [
        [
            'id' => null,
            'name' => null,
        ],
    ];

    public array $addresses = [];

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
        $this->related = app('laravel-crm.settings')->first('team');

        $this->phoneTypes = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes();
        $this->emailTypes = \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes();

        foreach (\VentureDrake\LaravelCrm\Models\AddressType::all() as $addressType) {
            $this->addressTypes[] = [
                'id' => $addressType->id,
                'name' => $addressType->name,
            ];
        }

        if ($this->related->phones->count() == 0) {
            $this->addPhone();
        } else {
            foreach ($this->related->phones as $phone) {
                $this->phones[] = [
                    'id' => $phone->id,
                    'number' => $phone->number,
                    'type' => $phone->type,
                    'primary' => $phone->primary,
                ];
            }
        }

        if ($this->related->emails->count() == 0) {
            $this->addEmail();
        } else {
            foreach ($this->related->emails as $email) {
                $this->emails[] = [
                    'id' => $email->id,
                    'address' => $email->address,
                    'type' => $email->type,
                    'primary' => $email->primary,
                ];
            }
        }

        if ($this->related->addresses->count() == 0) {
            $this->addAddress();
        } else {
            foreach ($this->related->addresses as $address) {
                $this->addresses[] = [
                    'id' => $address->id,
                    'type' => $address->address_type_id,
                    'name' => $address->name,
                    'contact' => $address->contact,
                    'phone' => $address->phone,
                    /* 'address' => $address->address, */
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'line3' => $address->line3,
                    'city' => $address->city,
                    'state' => $address->state,
                    'code' => $address->code,
                    'country' => $address->country,
                    'primary' => $address->primary,
                ];
            }
        }
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

        $related = app('laravel-crm.settings')->first('team');

        $this->updateRelatedPhones($related, $this->phones);
        $this->updateRelatedEmails($related, $this->emails);
        $this->updateRelatedAddresses($related, $this->addresses);

        $this->success(
            ucfirst(trans('laravel-crm::lang.settings_updated'))
        );
    }

    public function addPhone()
    {
        $this->phones[] = [
            'id' => null,
            'number' => null,
            'type' => null,
            'primary' => null,
        ];
    }

    public function deletePhone($index)
    {
        unset($this->phones[$index]);
    }

    public function addEmail()
    {
        $this->emails[] = [
            'id' => null,
            'address' => null,
            'type' => null,
            'primary' => null,
        ];
    }

    public function deleteEmail($index)
    {
        unset($this->emails[$index]);
    }

    public function addAddress()
    {
        $this->addresses[] = [
            'id' => null,
            'type' => null,
            'name' => null,
            'contact' => null,
            'phone' => null,
            'address' => null,
            'line1' => null,
            'line2' => null,
            'line3' => null,
            'city' => null,
            'state' => null,
            'code' => null,
            'country' => app('laravel-crm.settings')->get('country', 'United States'),
        ];
    }

    public function deleteAddress($index)
    {
        unset($this->addresses[$index]);
    }

    protected function updateRelatedPhones($setting, $phones)
    {
        $phoneIds = [];
        if ($phones) {
            foreach ($phones as $phoneRequest) {
                if ($phoneRequest['id'] && $phone = Phone::find($phoneRequest['id'])) {
                    $phone->update([
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                } elseif ($phoneRequest['number']) {
                    $phone = $setting->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                }
            }
        }

        foreach ($setting->phones as $phone) {
            if (! in_array($phone->id, $phoneIds)) {
                $phone->delete();
            }
        }
    }

    protected function updateRelatedEmails($setting, $emails)
    {
        $emailIds = [];

        if ($emails) {
            foreach ($emails as $emailRequest) {
                if ($emailRequest['id'] && $email = Email::find($emailRequest['id'])) {
                    $email->update([
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                } elseif ($emailRequest['address']) {
                    $email = $setting->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                }
            }
        }

        foreach ($setting->emails as $email) {
            if (! in_array($email->id, $emailIds)) {
                $email->delete();
            }
        }
    }

    protected function updateRelatedAddresses($setting, $addresses)
    {
        $addressIds = [];

        if ($addresses) {
            foreach ($addresses as $addressRequest) {
                if ($addressRequest['id'] && $address = Address::find($addressRequest['id'])) {
                    $address->update([
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                } else {
                    $address = $setting->addresses()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                }
            }
        }

        foreach ($setting->addresses as $address) {
            if (! in_array($address->id, $addressIds)) {
                $address->delete();
            }
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.setting-edit');
    }
}
