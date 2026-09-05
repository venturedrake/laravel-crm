<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Support\Modules;
use VentureDrake\LaravelCrm\Support\PdfContactDetails;

class SettingEdit extends Component
{
    use AuthorizesRequests;
    use Toast;
    use WithFileUploads;

    /**
     * The tabs this page is split across, in display order, mapped to the
     * modules any one of which must be enabled for the tab to be shown. An
     * empty list means the tab is never gated.
     *
     * Every entity gets its own tab, in the order its records flow through the
     * CRM, so a tab is always the one place that entity's settings live. Lead
     * and deal carry only an ID prefix today, which is fine — a near-empty tab
     * beats a shared one an admin has to learn the contents of.
     *
     * `documents` holds what applies across document types and sits ahead of
     * them for that reason. It is ungated for the same reason
     * `pdf_contact_details` carries no module directive: it feeds quote, order,
     * delivery and invoice PDFs alike.
     *
     * @var array<string, array<int, string>>
     */
    protected const TABS = [
        'general' => [],
        'leads' => ['leads'],
        'deals' => ['deals'],
        'documents' => [],
        'quotes' => ['quotes'],
        'orders' => ['orders'],
        'invoices' => ['invoices'],
        'deliveries' => ['deliveries'],
        'purchase-orders' => ['purchase-orders'],
    ];

    /**
     * Which tab each field lives on, so a validation failure can surface the
     * offending control rather than leaving Save looking inert while the error
     * sits on a hidden panel.
     *
     * @var array<string, array<int, string>>
     */
    protected const TAB_FIELDS = [
        'general' => [
            'organizationName', 'vatNumber', 'logoFile', 'logo', 'country', 'language',
            'currency', 'timezone', 'dateFormat', 'timeFormat', 'taxName', 'taxRate',
            'showRelatedActivity', 'dynamicProducts', 'phones', 'emails', 'addresses',
        ],
        'leads' => ['leadPrefix'],
        'deals' => ['dealPrefix'],
        'documents' => ['pdfContactDetails'],
        'quotes' => ['quotePrefix', 'quoteTerms'],
        'orders' => ['orderPrefix'],
        'invoices' => [
            'invoicePrefix', 'invoiceContactDetails', 'invoiceTerms', 'invoicePaymentInstructions',
        ],
        'deliveries' => ['deliveryPrefix'],
        'purchase-orders' => [
            'purchaseOrderPrefix', 'purchaseOrderTerms', 'purchaseOrderDeliveryInstructions',
        ],
    ];

    /**
     * The currently-visible tab. Query-string-synced so `?tab=invoices`
     * deep-links, matching the tab-state contract on Settings → Templates.
     */
    #[Url]
    public string $tab = 'general';

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

    /**
     * The shared "From" contact block, used wherever a doc type has no
     * override of its own. Printed on quote, order and delivery PDFs on the
     * themed templates and on invoice PDFs everywhere; PdfContactDetails
     * carries the full matrix and why `classic` and purchase orders differ.
     */
    public $pdfContactDetails;

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
        // Before anything else: an unrecognised `?tab=` leaves no radio checked,
        // and `.tab-content` is display:none until one is — a tab strip above a
        // blank void. `?tab=` on its own arrives here as ''.
        $this->normaliseTab();

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
        $this->pdfContactDetails = app('laravel-crm.settings')->get(PdfContactDetails::SHARED_KEY);
        $this->invoiceContactDetails = app('laravel-crm.settings')->get(PdfContactDetails::settingKey('invoice'));
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

        foreach (AddressType::all() as $addressType) {
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

    /**
     * Guards `$wire.set('tab', ...)` from the console the same way mount()
     * guards the query string.
     */
    public function updatedTab(): void
    {
        $this->normaliseTab();
    }

    public function save()
    {
        $this->authorize('update', Setting::class);

        // Every field is submitted on every save regardless of which tab is
        // showing, so a failure can be on a panel the admin cannot see. Jump to
        // it, then rethrow untouched — nothing else about the save changes.
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->tab = $this->tabForField(array_key_first($e->validator->errors()->messages()));
            $this->normaliseTab();

            throw $e;
        }

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

        // Both halves of the contact-block chain guard on `!== null` rather
        // than the truthy check their neighbours use, because a truthy guard
        // makes a field write-once: clearing the textarea binds '', which
        // skips the set(), leaves the old row in place, and lets mount()
        // silently restore the stale value to the form on the next visit.
        //
        // That matters for the shared key because it feeds the "From" block
        // on four doc types at once, and it matters just as much for the
        // invoice override, which shadows the shared value — write-once there
        // would mean an admin who once filled the invoice field could never
        // fall back to the shared block, which is exactly what the field's
        // hint tells them it does.
        //
        // Null still means "never filled on this install", so an untouched
        // field writes no row. PdfContactDetails::for() reads with filled(),
        // so a cleared row resolves to null and falls through the chain.
        if ($this->pdfContactDetails !== null) {
            app('laravel-crm.settings')->set(PdfContactDetails::SHARED_KEY, $this->pdfContactDetails);
        }

        if ($this->invoiceContactDetails !== null) {
            app('laravel-crm.settings')->set(PdfContactDetails::settingKey('invoice'), $this->invoiceContactDetails);
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
                        'line1' => $addressRequest['line1'] ?? null,
                        'line2' => $addressRequest['line2'] ?? null,
                        'line3' => $addressRequest['line3'] ?? null,
                        'city' => $addressRequest['city'] ?? null,
                        'state' => $addressRequest['state'] ?? null,
                        'code' => $addressRequest['code'] ?? null,
                        'country' => $addressRequest['country'] ?? null,
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
                        'line1' => $addressRequest['line1'] ?? null,
                        'line2' => $addressRequest['line2'] ?? null,
                        'line3' => $addressRequest['line3'] ?? null,
                        'city' => $addressRequest['city'] ?? null,
                        'state' => $addressRequest['state'] ?? null,
                        'code' => $addressRequest['code'] ?? null,
                        'country' => $addressRequest['country'] ?? null,
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

    /**
     * The tabs to render, in declaration order, after module gating.
     *
     * @return array<int, string>
     */
    protected function visibleTabs(): array
    {
        return array_keys(array_filter(
            self::TABS,
            fn (array $modules) => Modules::anyEnabled($modules)
        ));
    }

    /**
     * Fall back to `general` whenever `$tab` is not a tab currently on screen —
     * unknown name, empty string, or a real tab whose module is switched off.
     */
    protected function normaliseTab(): void
    {
        if (! in_array($this->tab, $this->visibleTabs(), true)) {
            $this->tab = 'general';
        }
    }

    /**
     * The tab holding `$field`, for jumping to a validation failure.
     */
    protected function tabForField(?string $field): string
    {
        if ($field === null) {
            return 'general';
        }

        foreach (self::TAB_FIELDS as $tab => $fields) {
            if (in_array($field, $fields, true)) {
                return $tab;
            }
        }

        return 'general';
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.setting-edit', [
            'tabs' => $this->visibleTabs(),
        ]);
    }
}
