<?php

namespace VentureDrake\LaravelCrm\Http\Livewire;

use Livewire\Component;

class LiveAddressEdit extends Component
{
    public $addresses;
    public $address;
    public $type;
    public $contact;
    public $line1;
    public $line2;
    public $line3;
    public $code;
    public $city;
    public $state;
    public $country;
    public $primary;
    public $addressId;
    public $old;
    public $model;
    public $updateMode = false;
    public $inputs = [];
    public $i = 0;

    public function mount($addresses, $old, $model = null)
    {
        $this->addresses = $addresses;
        $this->old = $old;
        $this->model = $model;

        if ($this->old) {
            foreach ($this->old as $address) {
                $this->add($this->i);
                $this->address[$this->i] = $address['address'] ?? null;
                $this->type[$this->i] = $address['type'] ?? null;
                $this->contact[$this->i] = $address['contact'] ?? null;
                $this->line1[$this->i] = $address['line1'] ?? null;
                $this->line2[$this->i] = $address['line2'] ?? null;
                $this->line3[$this->i] = $address['line3'] ?? null;
                $this->code[$this->i] = $address['code'] ?? null;
                $this->city[$this->i] = $address['city'] ?? null;
                $this->state[$this->i] = $address['state'] ?? null;
                $this->country[$this->i] = $address['country'] ?? null;
                $this->primary[$this->i] = $address['primary'] ?? null;
                $this->addressId[$this->i] = $address['id'] ?? null;
            }
        } elseif ($this->addresses && $this->addresses->count() > 0) {
            foreach ($this->addresses as $address) {
                $this->add($this->i);
                $this->address[$this->i] = $address->address;
                $this->type[$this->i] = $address->addressType->id ?? null;
                $this->contact[$this->i] = $address->contact;
                $this->line1[$this->i] = $address->line1;
                $this->line2[$this->i] = $address->line2;
                $this->line3[$this->i] = $address->line3;
                $this->code[$this->i] = $address->code;
                $this->city[$this->i] = $address->city;
                $this->state[$this->i] = $address->state;
                $this->country[$this->i] = $address->country;
                $this->primary[$this->i] = $address->primary;
                $this->addressId[$this->i] = $address->id;
            }
        } else {
            $this->add($this->i);
            
            if ($model == 'order') {
                $this->type[$this->i] = 5;
                $this->add($this->i);
                $this->type[$this->i] = 6;
            } elseif ($model == 'delivery') {
                $this->type[$this->i] = 6;
            }
        }
    }

    public function add($i)
    {
        $i = $i + 1;
        $this->i = $i;
        array_push($this->inputs, $i);
        $this->country[$i] = \VentureDrake\LaravelCrm\Models\Setting::country()->value;
        $this->dispatchBrowserEvent('addAddressInputs');
    }

    public function remove($i)
    {
        unset($this->inputs[$i]);
    }
    
    public function render()
    {
        return view('laravel-crm::livewire.address-edit');
    }
}
