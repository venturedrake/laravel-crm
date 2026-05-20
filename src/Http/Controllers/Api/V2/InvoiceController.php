<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreInvoiceRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateInvoiceRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\InvoiceResource;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Services\InvoiceService;

class InvoiceController extends ApiController
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()->with(['ownerUser', 'person', 'organization', 'labels', 'invoiceLines.product']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'total'],
            '-created_at'
        );

        $invoices = $query->paginate($this->perPage($request))->withQueryString();

        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['ownerUser', 'person', 'organization', 'labels', 'invoiceLines.product']);

        return new InvoiceResource($invoice);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $this->authorize('create', Invoice::class);

        [$payload, $person, $organization] = $this->buildPayload($request);

        $invoice = $this->invoiceService->create($payload, $person, $organization);

        $invoice->refresh()->load(['ownerUser', 'person', 'organization', 'labels', 'invoiceLines.product']);

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        [$payload, $person, $organization] = $this->buildPayload($request, $invoice);

        $this->invoiceService->update($payload, $invoice, $person, $organization);

        $invoice->refresh()->load(['ownerUser', 'person', 'organization', 'labels', 'invoiceLines.product']);

        return new InvoiceResource($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Invoice $existing = null): array
    {
        $person = $request->filled('person_id')
            ? Person::where('external_id', $request->input('person_id'))->first()
            : ($existing?->person);

        $organization = $request->filled('organization_id')
            ? Organization::where('external_id', $request->input('organization_id'))->first()
            : ($existing?->organization);

        $order = $request->filled('order_id')
            ? Order::where('external_id', $request->input('order_id'))->first()
            : null;

        $labelIds = collect($request->input('labels', []))
            ->map(fn ($uuid) => Label::where('external_id', $uuid)->value('id'))
            ->filter()
            ->values()
            ->all();

        $products = $this->buildProducts($request->input('line_items', []), $existing);

        $payload = (object) [
            'reference' => $request->input('reference', $existing?->reference),
            'issue_date' => $request->input('issue_date', $existing?->issue_date?->toIso8601String()),
            'due_date' => $request->input('due_date', $existing?->due_date?->toIso8601String()),
            'currency' => $request->input('currency', $existing?->currency) ?: 'USD',
            'terms' => $request->input('terms', $existing?->terms),
            'sub_total' => $request->input('subtotal', $existing && $existing->subtotal !== null ? $existing->subtotal / 100 : null),
            'tax' => $request->input('tax', $existing && $existing->tax !== null ? $existing->tax / 100 : null),
            'total' => $request->input('total', $existing && $existing->total !== null ? $existing->total / 100 : null),
            'order_id' => $order?->id ?? $existing?->order_id,
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id ?? $request->user()?->id),
            'labels' => $labelIds,
            'products' => $products,
        ];

        return [$payload, $person, $organization];
    }

    private function buildProducts(array $lineItems, ?Invoice $existing): ?array
    {
        if (empty($lineItems)) {
            return null;
        }

        return collect($lineItems)->map(function (array $item) use ($existing) {
            $productId = Product::where('external_id', $item['product_id'])->value('id');

            $invoiceLineId = null;
            if ($existing && ! empty($item['id'])) {
                $invoiceLineId = InvoiceLine::where('external_id', $item['id'])
                    ->where('invoice_id', $existing->id)
                    ->value('id');
            }

            return [
                'id' => $productId,
                'product_id' => $productId,
                'invoice_line_id' => $invoiceLineId,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['amount'],
                'comments' => $item['comments'] ?? null,
            ];
        })->all();
    }
}
