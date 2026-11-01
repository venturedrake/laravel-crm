<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use VentureDrake\LaravelCrm\Models\Product;

/**
 * Server-side search for the product picker on a line item row - shared by
 * crm-model-products and the deal form.
 *
 * Each row used to render a plain select holding every product, so a
 * catalogue of thousands was queried and rendered once per row on every
 * round trip (clicking +, changing a quantity, typing a comment), and the
 * deal form also serialised the lot into its snapshot. The options are
 * searched and capped here instead.
 *
 * Search terms are kept per row, so searching one row leaves the options of
 * the others alone.
 */
trait HasProductSearch
{
    /**
     * The search term typed into each row's picker, keyed by row.
     *
     * @var array<string, string>
     */
    public array $productSearch = [];

    /**
     * Per-request memo of the unsearched matches, shared by every row that
     * is not being searched. Protected so it is not serialised into the
     * Livewire snapshot.
     *
     * @var array<int, array{id: int, name: string, code: ?string}>|null
     */
    protected ?array $defaultProductOptions = null;

    /**
     * MaryUI calls `searchProducts('KEY')` as `searchProducts(value, 'KEY')`.
     */
    public function searchProducts(string $value = '', $row = null): void
    {
        if ($row === null) {
            return;
        }

        $this->productSearch[(string) $row] = $value;
    }

    /**
     * Up to 20 products matching the row's search, by name or code, plus the
     * product the row already holds.
     *
     * @return array<int, array{id: int, name: string, code: ?string}>
     */
    public function productOptions($row, $selectedId = null): array
    {
        $term = trim($this->productSearch[(string) $row] ?? '');

        if ($term === '') {
            $matches = $this->defaultProductOptions ??= $this->productMatches('');
        } else {
            $matches = $this->productMatches($term);
        }

        // MaryUI reads the selected label out of the options it was handed,
        // so a product outside the matches - or a since-deleted one still on
        // an existing line - would otherwise render blank.
        if (is_numeric($selectedId) && ! collect($matches)->contains('id', (int) $selectedId)) {
            if ($selected = Product::withTrashed()->select('id', 'name', 'code')->find($selectedId)) {
                $matches[] = $this->productOption($selected);
            }
        }

        return collect($matches)->unique('id')->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    /**
     * Forget a row's search once a product has been picked or the row removed.
     */
    protected function clearProductSearch($row): void
    {
        unset($this->productSearch[(string) $row]);
    }

    /**
     * @return array<int, array{id: int, name: string, code: ?string}>
     */
    protected function productMatches(string $term): array
    {
        return Product::query()
            ->select('id', 'name', 'code')
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('code', 'like', '%'.$term.'%')))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Product $product) => $this->productOption($product))
            ->all();
    }

    /**
     * @return array{id: int, name: string, code: ?string}
     */
    protected function productOption(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
        ];
    }
}
