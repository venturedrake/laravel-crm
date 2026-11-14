<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use VentureDrake\LaravelCrm\Support\Money;

trait SavesWithModelProducts
{
    /**
     * The preparation result is still client input. Apply only line items,
     * derive totals from them, then run the normal authorization and validation
     * in save() in this same request. Preview events cannot choose these rows.
     */
    public function saveWithProducts(array $payload)
    {
        validator($payload, [
            'products' => 'present|array',
            'products.*' => 'array',
        ])->validate();

        $subtotal = 0;
        $tax = 0;
        foreach ($payload['products'] as $row) {
            $subtotal += Money::toInteger($row['amount'] ?? 0) ?? 0;
            $tax += Money::toInteger($row['tax_amount'] ?? 0) ?? 0;
        }

        $this->updateProducts($payload['products'], $subtotal / 100, $tax / 100, ($subtotal + $tax) / 100);

        return $this->save();
    }
}
