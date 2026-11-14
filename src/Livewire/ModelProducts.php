<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Traits\HasProductSearch;
use VentureDrake\LaravelCrm\Livewire\Traits\HasVariationOptions;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Support\LineAmount;
use VentureDrake\LaravelCrm\Support\Money;
use VentureDrake\LaravelCrm\Support\Quantity;

class ModelProducts extends Component
{
    use HasProductSearch;
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

    /**
     * Each row's quantity and price, by row key, as they stood before this
     * request's updates. Protected, so it lives for one request only.
     *
     * @var array<string, string>|null
     */
    protected ?array $pricedBefore = null;

    /**
     * A paid invoice's saved lines by id, read once per request.
     *
     * @var Collection<int, InvoiceLine>|null
     */
    protected ?Collection $savedLines = null;

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
                    foreach ($this->model->quoteProducts()->orderBy('order')->orderBy('id')->get() as $quoteProduct) {
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
                    foreach ($this->model->orderProducts()->orderBy('order')->orderBy('id')->get() as $orderProduct) {
                        // Reset per iteration - leaving it set leaks the previous
                        // line's remainder onto a line that does not draw down.
                        $quantityRemaining = null;

                        if ($this->creating == 'Invoice' && $this->from == 'Order') {
                            $quantityRemaining = $orderProduct->invoiceRemainder()['quantity'];
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

                        $invoicingFromOrder = $this->creating == 'Invoice' && $this->from == 'Order';

                        // A line already invoiced in full has nothing left to bill.
                        if ($invoicingFromOrder && Quantity::isZero($quantityRemaining)) {
                            continue;
                        }

                        $index = count($this->products);

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

                        // The order line's amount and tax are for its full
                        // quantity, so a follow-up invoice seeded with only
                        // the remainder has to be priced from that remainder.
                        if ($invoicingFromOrder) {
                            $this->recalculateProduct($index, Product::withTrashed()->find($orderProduct->product_id));
                        }
                    }
                    break;

                case 'Invoice':
                    foreach ($this->model->invoiceLines()->orderBy('order')->orderBy('id')->get() as $invoiceLine) {
                        $index = count($this->products);

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

                        // InvoiceService prices every line from its unit price
                        // and quantity on save, so the form shows that from
                        // the start - not a stored amount that is out of step
                        // with them, which a save would quietly replace. Once
                        // the invoice is paid the service keeps a line's saved
                        // pricing instead, so the form shows that.
                        if (! $this->settledInvoice()) {
                            $this->recalculateProduct($index, Product::withTrashed()->find($invoiceLine->product_id));
                        }
                    }
                    break;

                case 'Delivery':
                    foreach ($this->model->deliveryProducts()->orderBy('order')->orderBy('id')->get() as $deliveryProduct) {
                        $this->products[] = [
                            'delivery_product_id' => $deliveryProduct->id,
                            'id' => $deliveryProduct->orderProduct->product_id,
                            'product_variation_id' => $deliveryProduct->orderProduct->product_variation_id,
                            'name' => $deliveryProduct->displayName(),
                            'quantity' => $deliveryProduct->quantity,
                            // The most this delivery can hold: what the order
                            // line has left once the other deliveries are counted,
                            // or the line's saved quantity if that is more - an
                            // unraised line is not re-capped on save, so a line
                            // over-delivered since its order was reduced can be
                            // lowered to anything up to what it already holds.
                            'order_product_id' => $deliveryProduct->order_product_id,
                            'quantity_max' => $deliveryProduct->orderProduct
                                ? max(
                                    0.0,
                                    $deliveryProduct->orderProduct->deliveryRemainder($this->model->id),
                                    Quantity::round($deliveryProduct->quantity),
                                )
                                : null,
                        ];
                    }

                    break;

                case 'PurchaseOrder':
                    foreach ($this->model->purchaseOrderLines()->orderBy('order')->orderBy('id')->get() as $purchaseOrderLine) {
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
                    foreach ($this->model->dealProducts()->whereNotNull('product_id')->orderBy('order')->orderBy('id')->get() as $dealProduct) {
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

            $this->keyRows();

            $this->recalculateTotals();

            $this->dispatchProductsUpdated();
        }

        $this->dynamicProducts = (app('laravel-crm.settings')->get('dynamic_products')) ? true : false;
    }

    /**
     * Before the request's updates, note each row's product, variation,
     * quantity and price, so the update can tell the rows the user changed
     * from the ones it merely resent.
     */
    public function updatingProducts($value, $key = null): void
    {
        // Once per request, ahead of the first update: Livewire sets every
        // update in a request before it runs any "updated" hook, so a later
        // snapshot would already hold an earlier update's new values.
        if ($this->pricedBefore !== null) {
            return;
        }

        $this->pricedBefore = [];

        foreach ($this->products as $row) {
            if (isset($row['row_key'])) {
                $this->pricedBefore[$row['row_key']] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'variation' => (string) ($row['product_variation_id'] ?? ''),
                    'price' => $this->priceSignature($row),
                ];
            }
        }
    }

    /**
     * A row's quantity and unit price, normalised so the money mask's
     * reformatting ("1,200.00" for 1200) does not read as a change.
     */
    protected function priceSignature(array $row): string
    {
        $quantity = $row['quantity'] ?? null;

        return (Money::toInteger($row['unit_price'] ?? null) ?? '').'|'
            .($quantity === null || $quantity === '' ? '' : Quantity::round($quantity));
    }

    /**
     * The row as it stood before this request's updates, or null for a row
     * with no key or one the snapshot does not know (a new row).
     *
     * @return array{id: string, variation: string, price: string}|null
     */
    protected function rowBefore($index): ?array
    {
        $rowKey = $this->products[$index]['row_key'] ?? null;

        return $rowKey !== null ? ($this->pricedBefore[$rowKey] ?? null) : null;
    }

    /**
     * Whether a row's quantity and unit price are as they were before this
     * request's updates.
     */
    protected function priceUnchanged($index): bool
    {
        $before = $this->rowBefore($index);

        return $before !== null && $before['price'] === $this->priceSignature($this->products[$index]);
    }

    /**
     * Whether a row keeps its amount rather than being repriced as unit
     * price x quantity: only when neither changed in this request. An
     * invoice line never does - InvoiceService prices every line from its
     * unit price and quantity on save, so the form has to show that - until
     * the invoice is paid, when the service keeps an unchanged line as saved.
     */
    protected function keepsAmount($index): bool
    {
        if ($this->creating === 'Invoice') {
            return false;
        }

        return $this->priceUnchanged($index);
    }

    /**
     * Whether a row stands exactly as saved, tax included: a line of a paid
     * invoice at the product, price and quantity it was saved with, which
     * InvoiceService::lineUnchanged() will not reprice either. Judged against
     * the saved line, as the service does - not against the start of this
     * request, or a quantity put back where it was would read as a change.
     */
    protected function keepsSavedPricing($index): bool
    {
        if (! $this->settledInvoice() || ! $saved = $this->savedLine($index)) {
            return false;
        }

        $row = $this->products[$index];

        return (string) ($row['id'] ?? '') === (string) $saved->product_id
            && Money::toInteger($row['unit_price'] ?? null) === (int) $saved->getRawOriginal('price')
            && Quantity::equals($row['quantity'] ?? null, $saved->quantity);
    }

    /**
     * Put a row back to the amount, tax and rate its line was saved with -
     * what the service will keep, so what the form shows.
     */
    protected function restoreSavedPricing($index): void
    {
        $saved = $this->savedLine($index);

        $this->products[$index]['tax_rate'] = $saved->tax_rate;
        $this->setMoney($index, 'tax_amount', (int) $saved->tax_amount);
        $this->setMoney($index, 'amount', (int) $saved->amount);
    }

    /**
     * The invoice line a row was loaded from, if it has one.
     */
    protected function savedLine($index): ?InvoiceLine
    {
        $lineId = $this->products[$index]['invoice_line_id'] ?? null;

        if (! $lineId) {
            return null;
        }

        $this->savedLines ??= $this->model->invoiceLines()->get()->keyBy('id');

        return $this->savedLines->get((int) $lineId);
    }

    /**
     * The order line an invoice row draws down, taken the way InvoiceService
     * takes it: on a new invoice, the row's line of the order it is made
     * from; on an edit, the line the invoice line was saved against.
     */
    protected function invoicedOrderProduct($index): ?OrderProduct
    {
        if ($this->creating !== 'Invoice') {
            return null;
        }

        if ($this->model instanceof Invoice) {
            return $this->savedLine($index)?->orderProduct;
        }

        if ($this->model instanceof Order && ! empty($this->products[$index]['order_product_id'])) {
            return $this->model->orderProducts()->find($this->products[$index]['order_product_id']);
        }

        return null;
    }

    /**
     * The form is editing an invoice with a payment recorded, whose lines
     * and totals InvoiceService::isSettled() fixes.
     */
    protected function settledInvoice(): bool
    {
        return $this->model instanceof Invoice
            && ((int) $this->model->getRawOriginal('amount_paid') > 0 || $this->model->fully_paid_at !== null);
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
                $this->pickProduct($index, $value);
            } elseif ($updating[1] == 'product_variation_id') {
                $this->pickVariation($index, $value);
            } else {
                // Any other field - the mask reformatting the amount, a
                // comment typed - leaves the amount alone unless it changed
                // the quantity or price, as in a wholesale update below.
                if ($this->keepsSavedPricing($index)) {
                    $this->restoreSavedPricing($index);
                } else {
                    $this->recalculateRow($index, keepAmount: $this->keepsAmount($index));
                }
            }
        } else {
            // Livewire sends the array wholesale whenever every row changed -
            // which the money mask makes the usual case, as it reformats each
            // row's read-only amount - so a product or variation picked, or a
            // quantity typed, can arrive here rather than as its own update.
            // A row zeroed here has to reprice, or it keeps its amount in the
            // totals. A row whose quantity and price did not change keeps its
            // amount (an API-discounted quote line is not price x quantity),
            // but its tax is still taken at the product's rate, as the
            // services will save it.
            $this->prepareRows();
        }

        $this->recalculateTotals();

        $this->dispatchProductsUpdated();
    }

    /**
     * Prepare the values captured by Save, independently of preview events.
     * Keep invalid quantities intact so the parent's validation can reject them.
     */
    public function prepareForSave(array $rows): array
    {
        validator(['products' => $rows], [
            'products' => 'present|array',
            'products.*' => 'array',
        ])->validate();

        $this->updatingProducts($rows);
        $current = collect($this->products)->keyBy('row_key');

        foreach ($rows as &$row) {
            $saved = $current->get($row['row_key'] ?? '');

            // An earlier preview may have finished since the browser captured
            // these rows. Its calculated amount is newer than the captured one.
            if ($saved
                && (string) ($saved['id'] ?? '') === (string) ($row['id'] ?? '')
                && (string) ($saved['product_variation_id'] ?? '') === (string) ($row['product_variation_id'] ?? '')
                && $this->priceSignature($saved) === $this->priceSignature($row)) {
                $row['amount'] = $saved['amount'] ?? null;
            }
        }
        unset($row);

        $this->products = $rows;
        $this->prepareRows(clamp: false);
        $this->recalculateTotals();

        return [
            'products' => $this->products,
            'sub_total' => $this->sub_total,
            'tax' => $this->tax,
            'total' => $this->total,
        ];
    }

    protected function prepareRows(bool $clamp = true): void
    {
        foreach (array_keys($this->products) as $index) {
            if ($clamp) {
                $this->clampQuantity($index);
            }

            $before = $this->rowBefore($index);
            $row = $this->products[$index];

            if ($before && (string) ($row['id'] ?? '') !== $before['id']) {
                $this->pickProduct($index, $row['id'] ?? null);
            } elseif ($before && (string) ($row['product_variation_id'] ?? '') !== $before['variation']) {
                $this->pickVariation($index, $row['product_variation_id'] ?? null);
            } elseif ($this->keepsSavedPricing($index)) {
                $this->restoreSavedPricing($index);
            } else {
                $this->recalculateRow($index, keepAmount: $this->keepsAmount($index));
            }
        }
    }

    /**
     * A product picked on a row: its default price, no variation.
     */
    protected function pickProduct($index, $productId): void
    {
        // The pick is made, so the row's picker goes back to its full list.
        $this->clearProductSearch($this->products[$index]['row_key'] ?? $index);

        // A variation belongs to one product, so a new product clears it.
        $this->products[$index]['product_variation_id'] = null;

        if ($product = Product::find($productId)) {
            // With no variation this is the product's default price,
            // exactly as before variations existed.
            $this->products[$index]['unit_price'] = (($product->unitPriceFor(null, $this->currency) ?? 0) / 100);

            // On a paid invoice the saved product picked again, at its saved
            // price, is the saved line - which the service keeps as it was.
            if ($this->keepsSavedPricing($index)) {
                $this->restoreSavedPricing($index);
            } else {
                $this->recalculateProduct($index, $product);
            }
        }
    }

    /**
     * A variation picked on a row: its price, or the product's.
     */
    protected function pickVariation($index, $variationId): void
    {
        // withTrashed, as in the services: a line kept on a since-deleted
        // product still lists its variations, so a pick must stick.
        $product = Product::withTrashed()->find($this->products[$index]['id'] ?? null);

        // Scoped to the row's product (and, through the global scope,
        // the team) - anything else is dropped rather than stored. The
        // placeholder option posts '', which resolves to null here.
        $variation = $product ? $this->resolveRowVariation($product->id, $variationId) : null;

        $this->products[$index]['product_variation_id'] = $variation?->id;

        if ($product) {
            // Falls back to the product price when the variation has
            // none in the document currency.
            $this->products[$index]['unit_price'] = (($product->unitPriceFor($variation, $this->currency) ?? 0) / 100);

            // On a paid invoice a variation at the saved price changes
            // nothing the service reprices, so nothing the form does.
            if ($this->keepsSavedPricing($index)) {
                $this->restoreSavedPricing($index);
            } else {
                $this->recalculateProduct($index, $product);
            }
        }
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

            $this->addError("products.$index.quantity", ucfirst(__('laravel-crm::lang.quantity_exceeds_remaining', [
                'remaining' => Quantity::format($max),
            ])));
        }

        $this->products[$index]['quantity'] = $quantity;
    }

    /**
     * Recalculate a single line from its unit price, quantity and tax rate.
     *
     * The rate is resolved the way the services resolve it on save, so the
     * tax the form shows is the tax the document gets. With no product - a
     * name typed in as a dynamic product, or one since hard-deleted - that is
     * the default rate, as the product the service creates for it carries
     * none of its own.
     *
     * $keepAmount re-taxes the row's current amount instead of repricing it
     * from unit price x quantity.
     */
    protected function recalculateProduct($index, ?Product $product, bool $keepAmount = false): void
    {
        $taxRate = $this->resolveTaxRate($product);

        if ($keepAmount) {
            $amount = Money::toInteger($this->products[$index]['amount'] ?? 0) ?? 0;
            $line = ['amount' => $amount, 'tax' => LineAmount::taxCents($amount, $taxRate)];
        } elseif ($orderProduct = $this->invoicedOrderProduct($index)) {
            // An invoice line taking the last of an order line bills exactly
            // what is left of it, as InvoiceService does.
            $line = $orderProduct->invoiceLineCents(
                $this->products[$index]['quantity'] ?? 1,
                Money::toInteger($this->products[$index]['unit_price'] ?? 0) ?? 0,
                $taxRate,
                $this->model instanceof Invoice ? $this->model->id : null,
            );
        } else {
            // In cents, the same way the CheckAmount helpers price the saved
            // line, so the two cannot disagree on a half-cent boundary.
            $line = LineAmount::forForm(
                $this->products[$index]['unit_price'] ?? 0,
                $this->products[$index]['quantity'] ?? 1,
                $taxRate,
            );
        }

        $this->products[$index]['tax_rate'] = $taxRate;
        $this->setMoney($index, 'tax_amount', $line['tax']);
        $this->setMoney($index, 'amount', $line['amount']);
    }

    /**
     * Set a row's money field from cents - unless it already holds that
     * value. The browser's money mask formats what the server sends
     * ("135.00" for 135), so rewriting an unchanged value makes every row
     * read as changed and Livewire resend the whole array on the next
     * update. The services read either form.
     */
    protected function setMoney($index, string $field, int $cents): void
    {
        $current = $this->products[$index][$field] ?? null;

        if ($current !== null && $current !== '' && Money::toInteger($current) === $cents) {
            return;
        }

        $this->products[$index][$field] = round($cents / 100, 2);
    }

    /**
     * Reprice a row that has a product picked. A delivery row has no price
     * to work from and a blank row nothing to price, so both are left alone.
     */
    protected function recalculateRow($index, bool $keepAmount = false): void
    {
        $row = $this->products[$index] ?? null;

        if (! $row || ! array_key_exists('unit_price', $row) || empty($row['id'])) {
            return;
        }

        // withTrashed, as in the services: a line kept on a since-deleted
        // product is still priced.
        $this->recalculateProduct($index, Product::withTrashed()->find($row['id']), $keepAmount);
    }

    protected function resolveTaxRate(?Product $product): float
    {
        if ($product?->taxRate) {
            return (float) $product->taxRate->rate;
        }

        if ($product?->tax_rate) {
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
     * ("14,000.00"), so they are normalised rather than cast. They are summed
     * as integer cents, so adding many lines picks up no float drift.
     */
    protected function recalculateTotals(): void
    {
        $subTotal = 0;
        $tax = 0;

        foreach ($this->products as $product) {
            $subTotal += Money::toInteger($product['amount'] ?? 0) ?? 0;
            $tax += Money::toInteger($product['tax_amount'] ?? 0) ?? 0;
        }

        $this->sub_total = round($subTotal / 100, 2);
        $this->tax = round($tax / 100, 2);
        $this->total = round(($subTotal + $tax) / 100, 2);
    }

    protected function dispatchProductsUpdated(): void
    {
        $this->dispatch('model-products-updated', products: $this->products, sub_total: $this->sub_total, tax: $this->tax, total: $this->total);
    }

    /**
     * Give each loaded row a stable key, so the view can follow a row by
     * identity rather than by its index once rows are dragged around.
     */
    protected function keyRows(): void
    {
        foreach ($this->products as $index => $product) {
            $this->products[$index]['row_key'] ??= (string) Str::uuid();
        }
    }

    public function add()
    {
        $this->products[] = [
            'row_key' => (string) Str::uuid(),
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
        $this->clearProductSearch($this->products[$index]['row_key'] ?? $index);

        unset($this->products[$index]);
        $this->products = array_values($this->products);

        $this->recalculateTotals();

        $this->dispatchProductsUpdated();
    }

    /**
     * Move a row to a new position, from a drag in the browser. The services
     * save each line's `order` from its position in the array, so the parent
     * form only needs the reordered rows.
     */
    public function moveProduct(int $from, int $to)
    {
        $count = count($this->products);

        if ($from === $to || $from < 0 || $to < 0 || $from >= $count || $to >= $count) {
            return;
        }

        $products = array_values($this->products);
        $row = array_splice($products, $from, 1);
        array_splice($products, $to, 0, $row);

        $this->products = $products;

        // Errors are keyed by row index, so they would land on the wrong rows.
        $this->resetErrorBag();

        $this->recalculateTotals();

        $this->dispatchProductsUpdated();
    }

    public function render()
    {
        return view('laravel-crm::livewire.model-products');
    }
}
