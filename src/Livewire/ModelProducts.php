<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Attributes\Reactive;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Traits\HasVariationOptions;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\Quantity;

class ModelProducts extends Component
{
    use HasVariationOptions;

    public ?string $creating = null;

    /**
     * The document currency, used to pick a variation's price. Bound from
     * the parent form so a currency change there reaches this component.
     */
    #[Reactive]
    public ?string $currency = null;

    public $model = null;

    public ?string $from;

    public array $products = [];

    public $sub_total = 0;

    public $discount = 0;

    public $tax = 0;

    public $adjustment = 0;

    public $total = 0;

    public bool $dynamicProducts = false;

    public bool $showCreateProduct = false;

    protected $listeners = [
        'product-created' => '$refresh',
    ];

    public function mount()
    {
        if (! $this->model) {
            $this->add();
        } else {
            switch (class_basename($this->model)) {
                case 'Quote':
                    foreach ($this->model->quoteProducts as $quoteProduct) {
                        $this->products[] = [
                            'quote_product_id' => $quoteProduct->id,
                            'id' => $quoteProduct->product_id,
                            'product_variation_id' => $quoteProduct->product_variation_id,
                            'name' => $quoteProduct->displayName(),
                            'quantity' => $quoteProduct->quantity,
                            'unit_price' => $quoteProduct->price / 100,
                            'tax_rate' => $quoteProduct->tax_rate,
                            'tax_amount' => $quoteProduct->tax_amount / 100,
                            'amount' => $quoteProduct->amount / 100,
                            'comments' => $quoteProduct->comments,
                        ];
                    }

                    break;

                case 'Order':
                    foreach ($this->model->orderProducts as $orderProduct) {
                        // Reset per iteration - leaving it set leaks the previous
                        // line's remainder onto a line that does not draw down.
                        $quantityRemaining = null;

                        if ($this->creating == 'Invoice' && $this->from == 'Order') {
                            $quantityRemaining = Quantity::toFloat($orderProduct->quantity);

                            foreach ($this->model->invoices as $invoice) {
                                if ($invoiceProduct = $invoice->invoiceLines()->where('order_product_id', $orderProduct->id)->first()) {
                                    $quantityRemaining -= Quantity::toFloat($invoiceProduct->quantity);
                                }
                            }
                        } elseif ($this->creating == 'Delivery' && $this->from == 'Order') {
                            $quantityRemaining = Quantity::toFloat($orderProduct->quantity);

                            foreach ($this->model->deliveries as $delivery) {
                                if ($deliveryProduct = $delivery->deliveryProducts()->where('order_product_id', $orderProduct->id)->first()) {
                                    $quantityRemaining -= Quantity::toFloat($deliveryProduct->quantity);
                                }
                            }
                        } elseif ($this->creating == 'PurchaseOrder' && $this->from == 'Order') {
                            // Not capped: the drawdown below is commented out, so
                            // there is no remainder to cap against.
                            /*foreach ($this->model->purchaseOrders as $purchaseOrder) {
                                 if ($purchaseOrderProduct = $purchaseOrder->purchaseOrderLines()->where('order_product_id', $orderProduct->id)->first()) {
                                     $quantityRemaining -= $purchaseOrderProduct->quantity;
                                 }
                            }*/
                        }

                        if ($quantityRemaining !== null) {
                            $quantityRemaining = max(0.0, Quantity::round($quantityRemaining));
                        }

                        $this->products[] = [
                            'order_product_id' => $orderProduct->id,
                            'id' => $orderProduct->product_id,
                            'product_variation_id' => $orderProduct->product_variation_id,
                            'name' => $orderProduct->displayName(),
                            'quantity_max' => $quantityRemaining,
                            'quantity' => $quantityRemaining ?? $orderProduct->quantity,
                            'unit_price' => $orderProduct->price / 100,
                            'tax_rate' => $orderProduct->tax_rate,
                            'tax_amount' => $orderProduct->tax_amount / 100,
                            'amount' => $orderProduct->amount / 100,
                            'comments' => $orderProduct->comments,
                        ];
                    }
                    break;

                case 'Invoice':
                    foreach ($this->model->invoiceLines as $invoiceLine) {
                        $this->products[] = [
                            'invoice_line_id' => $invoiceLine->id,
                            'id' => $invoiceLine->product_id,
                            'product_variation_id' => $invoiceLine->product_variation_id,
                            'name' => $invoiceLine->displayName(),
                            'quantity' => $invoiceLine->quantity,
                            'unit_price' => $invoiceLine->price / 100,
                            'tax_rate' => $invoiceLine->tax_rate,
                            'tax_amount' => $invoiceLine->tax_amount / 100,
                            'amount' => $invoiceLine->amount / 100,
                            'comments' => $invoiceLine->comments,
                        ];
                    }
                    break;

                case 'Delivery':
                    foreach ($this->model->deliveryProducts as $deliveryProduct) {
                        $this->products[] = [
                            'delivery_product_id' => $deliveryProduct->id,
                            'id' => $deliveryProduct->orderProduct->product_id,
                            'product_variation_id' => $deliveryProduct->orderProduct->product_variation_id,
                            'name' => $deliveryProduct->displayName(),
                            'quantity' => $deliveryProduct->quantity,
                        ];
                    }

                    break;

                case 'PurchaseOrder':
                    foreach ($this->model->purchaseOrderLines as $purchaseOrderLine) {
                        $this->products[] = [
                            'purchase_order_line_id' => $purchaseOrderLine->id,
                            'id' => $purchaseOrderLine->product_id,
                            'product_variation_id' => $purchaseOrderLine->product_variation_id,
                            'name' => $purchaseOrderLine->displayName(),
                            'quantity' => $purchaseOrderLine->quantity,
                            'unit_price' => $purchaseOrderLine->price / 100,
                            'tax_rate' => $purchaseOrderLine->tax_rate,
                            'tax_amount' => $purchaseOrderLine->tax_amount / 100,
                            'amount' => $purchaseOrderLine->amount / 100,
                            'comments' => $purchaseOrderLine->comments,
                        ];
                    }
                    break;

                case 'Deal':
                    // A quote created from a deal starts from the deal's lines.
                    // They stay editable - the form passes no `from` - and the
                    // deal_product_id lets the quote carry the line's variation
                    // across even if it has since been deleted.
                    foreach ($this->model->dealProducts()->whereNotNull('product_id')->get() as $dealProduct) {
                        $index = count($this->products);

                        $this->products[] = [
                            'deal_product_id' => $dealProduct->id,
                            'id' => $dealProduct->product_id,
                            'product_variation_id' => $dealProduct->product_variation_id,
                            'name' => $dealProduct->displayName(),
                            'quantity' => $dealProduct->quantity,
                            'quantity_max' => null,
                            'unit_price' => $dealProduct->price / 100,
                            'tax_rate' => null,
                            'tax_amount' => null,
                            'amount' => $dealProduct->amount / 100,
                            'comments' => $dealProduct->comments ?? null,
                        ];

                        // Deal lines carry no tax, so it is worked out here the
                        // same way picking the product on a blank row would.
                        if ($dealProduct->product) {
                            $this->recalculateProduct($index, $dealProduct->product);
                        }
                    }

                    if (! $this->products) {
                        $this->add();
                    }

                    break;

                default:
                    // Anything else (a lead, person or organization) has no
                    // lines to start from - give the form its blank row.
                    $this->add();

                    break;
            }

            $this->recalculateTotals();

            $this->dispatchProductsUpdated();
        }

        $this->dynamicProducts = (app('laravel-crm.settings')->get('dynamic_products')) ? true : false;
    }

    public function updatedProducts($value, $key)
    {
        $updating = explode('.', $key);

        // Anything shorter than "{index}.{attribute}" replaces rows wholesale rather
        // than editing one, so there is no single line to recalculate - the totals are
        // still summed from whatever the rows now hold.
        if (count($updating) > 1) {
            $index = $updating[0];

            // Ahead of the Product::find() below, because a Delivery line has no
            // product id and so never reaches recalculateProduct().
            $this->clampQuantity($index);

            if ($updating[1] == 'id') {
                // A variation belongs to one product, so a new product clears it.
                $this->products[$index]['product_variation_id'] = null;

                if ($product = Product::find($value)) {
                    // With no variation this is the product's default price,
                    // exactly as before variations existed.
                    $this->products[$index]['unit_price'] = (($product->unitPriceFor(null, $this->currency) ?? 0) / 100);

                    $this->recalculateProduct($index, $product);
                }
            } elseif ($updating[1] == 'product_variation_id') {
                // withTrashed, as in the services: a line kept on a since-deleted
                // product still lists its variations, so a pick must stick.
                $product = Product::withTrashed()->find($this->products[$index]['id'] ?? null);

                // Scoped to the row's product (and, through the global scope,
                // the team) - anything else is dropped rather than stored. The
                // placeholder option posts '', which resolves to null here.
                $variation = $product ? $this->resolveRowVariation($product->id, $value) : null;

                $this->products[$index]['product_variation_id'] = $variation?->id;

                if ($product) {
                    // Falls back to the product price when the variation has
                    // none in the document currency.
                    $this->products[$index]['unit_price'] = (($product->unitPriceFor($variation, $this->currency) ?? 0) / 100);

                    $this->recalculateProduct($index, $product);
                }
            } elseif ($product = Product::find($this->products[$index]['id'] ?? null)) {
                $this->recalculateProduct($index, $product);
            }
        } else {
            foreach (array_keys($this->products) as $index) {
                $this->clampQuantity($index);
            }
        }

        $this->recalculateTotals();

        $this->dispatchProductsUpdated();
    }

    /**
     * Round a typed quantity to the stored scale and hold it inside the
     * amount still outstanding on the order line it draws down.
     *
     * The invoice and delivery forms used to pick their quantity from a
     * `<select>` built by an integer loop over the remainder, which was the
     * only thing stopping an over-invoice - and only in the browser. A
     * decimal quantity cannot come from a dropdown, so the cap moves here.
     *
     * This is the live nudge, not the guard: `quantity_max` is a public
     * property and so round-trips through the browser. QuantityWithinRemaining
     * recomputes the remainder from the order line on submit.
     */
    protected function clampQuantity($index): void
    {
        if (! isset($this->products[$index]) || ! array_key_exists('quantity', $this->products[$index])) {
            return;
        }

        $value = $this->products[$index]['quantity'];

        // A blank or absent quantity stays blank - it is not a quantity of
        // zero, and turning it into one would have the services' isPositive()
        // guards drop the line.
        if ($value === null || $value === '') {
            return;
        }

        $quantity = max(0.0, Quantity::round($value));
        $max = $this->products[$index]['quantity_max'] ?? null;

        if ($max !== null && Quantity::greaterThan($quantity, $max)) {
            $quantity = Quantity::round($max);

            $this->addError("products.$index.quantity", __('laravel-crm::lang.quantity_exceeds_remaining', [
                'remaining' => Quantity::format($max),
            ]));
        }

        $this->products[$index]['quantity'] = $quantity;
    }

    /**
     * Recalculate a single line from its unit price, quantity and tax rate.
     */
    protected function recalculateProduct($index, Product $product): void
    {
        $taxRate = $this->resolveTaxRate($product);
        $quantity = Quantity::toFloat($this->products[$index]['quantity'] ?? 1);
        $unitPrice = Money::toFloat($this->products[$index]['unit_price'] ?? 0);

        $this->products[$index]['tax_rate'] = $taxRate;
        $this->products[$index]['tax_amount'] = round(($unitPrice * $quantity) * ($taxRate / 100), 2);
        $this->products[$index]['amount'] = round($unitPrice * $quantity, 2);
    }

    protected function resolveTaxRate(Product $product): float
    {
        if ($product->taxRate) {
            return (float) $product->taxRate->rate;
        }

        if ($product->tax_rate) {
            return (float) $product->tax_rate;
        }

        if ($defaultTaxRate = TaxRate::where('default', 1)->first()) {
            return (float) $defaultTaxRate->rate;
        }

        return (float) app('laravel-crm.settings')->get('tax_rate', 0);
    }

    /**
     * Sum the totals from the lines themselves.
     *
     * The line values come back from the masked inputs as formatted strings
     * ("14,000.00"), so they are normalised rather than cast.
     */
    protected function recalculateTotals(): void
    {
        $subTotal = 0;
        $tax = 0;

        foreach ($this->products as $product) {
            $subTotal += Money::toFloat($product['amount'] ?? 0);
            $tax += Money::toFloat($product['tax_amount'] ?? 0);
        }

        $this->sub_total = round($subTotal, 2);
        $this->tax = round($tax, 2);
        $this->total = round($subTotal + $tax, 2);
    }

    protected function dispatchProductsUpdated(): void
    {
        $this->dispatch('model-products-updated', products: $this->products, sub_total: $this->sub_total, tax: $this->tax, total: $this->total);
    }

    public function add()
    {
        $this->products[] = [
            'id' => null,
            'product_variation_id' => null,
            'name' => null,
            'quantity' => 1,
            'quantity_max' => null,
            'unit_price' => null,
            'tax_rate' => null,
            'tax_amount' => null,
            'amount' => null,
            'comments' => null,
        ];

        $this->dispatchProductsUpdated();
    }

    public function remove($index)
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);

        $this->recalculateTotals();

        $this->dispatchProductsUpdated();
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-products');
    }
}
