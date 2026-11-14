<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use VentureDrake\LaravelCrm\Http\Rules\QuantityWithinRemaining;

/**
 * Server-side rules for the line item quantity input shared by the quote,
 * order, invoice, purchase order, delivery and deal forms.
 *
 * Quantities are stored as decimal(15,3) so a product can be sold by weight
 * or volume, which means the input is a free number field rather than the
 * bounded dropdown the Order to Invoice and Order to Delivery forms used to
 * render. Nothing on the server checked the quantity before, because the
 * dropdown made an out-of-range value unreachable from the browser.
 */
trait HasLineItemQuantityRules
{
    /**
     * The model these lines are written to, when they draw down an order
     * line. Only the invoice and delivery forms do; everywhere else the
     * remaining-quantity rule is a no-op.
     *
     * `owner` is the parent column and id of the document being edited, so a
     * line left at or below its saved quantity is not re-capped.
     *
     * @return array{model: class-string, relation: string, key: string|null, owner?: array<string, int>|null}|null
     */
    protected function lineItemDrawdown(): ?array
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function lineItemQuantityRules(): array
    {
        $drawdown = $this->lineItemDrawdown();

        return [
            'products.*.quantity' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,3',
                new QuantityWithinRemaining(
                    $this->products ?? [],
                    $drawdown['model'] ?? null,
                    $drawdown['relation'] ?? null,
                    $drawdown['key'] ?? null,
                    $drawdown['owner'] ?? null,
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function lineItemQuantityMessages(): array
    {
        return [
            'products.*.quantity.decimal' => ucfirst(__('laravel-crm::lang.quantity_max_3_decimals')),
        ];
    }
}
