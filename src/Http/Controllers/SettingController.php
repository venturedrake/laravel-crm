<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use DB;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\UpdateSettingRequest;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Services\SettingService;

class SettingController extends Controller
{
    /**
     * @var SettingService
     */
    private $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        return view('laravel-crm::settings.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSettingRequest $request)
    {
        $this->settingService->set('organization_name', $request->organization_name);

        if ($request->vat_number) {
            $this->settingService->set('vat_number', $request->vat_number);
        }

        $this->settingService->set('language', $request->language);
        $this->settingService->set('country', $request->country);
        $this->settingService->set('currency', $request->currency);
        $this->settingService->set('timezone', $request->timezone);

        if ($request->tax_name) {
            $this->settingService->set('tax_name', $request->tax_name);
        }

        if ($request->tax_rate) {
            $this->settingService->set('tax_rate', $request->tax_rate);
        }

        if ($request->lead_prefix) {
            $this->settingService->set('lead_prefix', $request->lead_prefix);
        }

        if ($request->deal_prefix) {
            $this->settingService->set('deal_prefix', $request->deal_prefix);
        }

        if ($request->quote_prefix) {
            $this->settingService->set('quote_prefix', $request->quote_prefix);
        }

        if ($request->order_prefix) {
            $this->settingService->set('order_prefix', $request->order_prefix);
        }

        if ($request->invoice_prefix) {
            $this->settingService->set('invoice_prefix', $request->invoice_prefix);
        }

        if ($request->delivery_prefix) {
            $this->settingService->set('delivery_prefix', $request->delivery_prefix);
        }

        if ($request->purchase_order_prefix) {
            $this->settingService->set('purchase_order_prefix', $request->purchase_order_prefix);
        }

        if ($request->quote_terms) {
            $this->settingService->set('quote_terms', $request->quote_terms);
        }

        if ($request->invoice_contact_details) {
            $this->settingService->set('invoice_contact_details', $request->invoice_contact_details);
        }

        if ($request->invoice_terms) {
            $this->settingService->set('invoice_terms', $request->invoice_terms);
        }

        if ($request->invoice_payment_instructions) {
            $this->settingService->set('invoice_payment_instructions', $request->invoice_payment_instructions);
        }

        if ($request->purchase_order_terms) {
            $this->settingService->set('purchase_order_terms', $request->purchase_order_terms);
        }

        if ($request->purchase_order_delivery_instructions) {
            $this->settingService->set('purchase_order_delivery_instructions',
                $request->purchase_order_delivery_instructions);
        }

        $this->settingService->set('date_format', $request->date_format);
        $this->settingService->set('time_format', $request->time_format);

        if ($file = $request->file('logo')) {
            if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
                $filePath = 'laravel-crm/'.auth()->user()->currentTeam->id;
            } else {
                $filePath = 'laravel-crm';
            }

            $file->move(storage_path('app/public/'.$filePath), $file->getClientOriginalName());
            $this->settingService->set('logo_file', $filePath.'/'.$file->getClientOriginalName());
            $this->settingService->set('logo_file_name', $file->getClientOriginalName());
        }

        if ($request->organization_name && config('laravel-crm.teams') && auth()->user()->currentTeam) {
            DB::table('teams')
                ->where('id', auth()->user()->currentTeam->id)
                ->update(['name' => $request->organization_name]);
        }

        $this->settingService->set('dynamic_products', (($request->dynamic_products == 'on') ? 1 : 0));
        $this->settingService->set('show_related_activity', (($request->show_related_activity == 'on') ? 1 : 0));

        $related = $this->settingService->get('team');
        $this->updateRelatedPhones($related, $request->phones);
        $this->updateRelatedEmails($related, $request->emails);
        $this->updateRelatedAddresses($related, $request->addresses);

        flash(ucfirst(trans('laravel-crm::lang.settings_updated')))->success()->important();

        return back();
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
}
