<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreQuoteRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateQuoteRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\QuoteResource;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Services\QuoteService;

class QuoteController extends ApiController
{
    public function __construct(private QuoteService $quoteService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Quote::class);

        $query = Quote::query()->with(['ownerUser', 'person', 'organization', 'labels', 'quoteProducts.product']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'title', 'total'],
            '-created_at'
        );

        $quotes = $query->paginate($this->perPage($request))->withQueryString();

        return QuoteResource::collection($quotes);
    }

    public function show(Quote $quote)
    {
        $this->authorize('view', $quote);

        $quote->load(['ownerUser', 'person', 'organization', 'labels', 'quoteProducts.product']);

        return new QuoteResource($quote);
    }

    public function store(StoreQuoteRequest $request)
    {
        $this->authorize('create', Quote::class);

        [$payload, $person, $organization] = $this->buildPayload($request);

        $quote = $this->quoteService->create($payload, $person, $organization);

        $quote->refresh()->load(['ownerUser', 'person', 'organization', 'labels', 'quoteProducts.product']);

        return (new QuoteResource($quote))->response()->setStatusCode(201);
    }

    public function update(UpdateQuoteRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        [$payload, $person, $organization] = $this->buildPayload($request, $quote);

        $this->quoteService->update($payload, $quote, $person, $organization);

        $quote->refresh()->load(['ownerUser', 'person', 'organization', 'labels', 'quoteProducts.product']);

        return new QuoteResource($quote);
    }

    public function destroy(Quote $quote)
    {
        $this->authorize('delete', $quote);

        $quote->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Quote $existing = null): array
    {
        $person = $request->filled('person_id')
            ? Person::where('external_id', $request->input('person_id'))->first()
            : ($existing?->person);

        $organization = $request->filled('organization_id')
            ? Organization::where('external_id', $request->input('organization_id'))->first()
            : ($existing?->organization);

        $lead = $request->filled('lead_id')
            ? Lead::where('external_id', $request->input('lead_id'))->first()
            : null;

        $pipelineStage = $request->filled('pipeline_stage_id')
            ? PipelineStage::where('external_id', $request->input('pipeline_stage_id'))->first()
            : null;

        $labelIds = collect($request->input('labels', []))
            ->map(fn ($uuid) => Label::where('external_id', $uuid)->value('id'))
            ->filter()
            ->values()
            ->all();

        $products = $this->buildProducts($request->input('line_items', []), $existing);

        $payload = (object) [
            'title' => $request->input('title', $existing?->title),
            'description' => $request->input('description', $existing?->description),
            'reference' => $request->input('reference', $existing?->reference),
            'currency' => $request->input('currency', $existing?->currency) ?: 'USD',
            'issue_at' => $request->input('issue_at', $existing?->issue_at?->toIso8601String()),
            'expire_at' => $request->input('expire_at', $existing?->expire_at?->toIso8601String()),
            'terms' => $request->input('terms', $existing?->terms),
            'sub_total' => $request->input('subtotal', $existing && $existing->subtotal !== null ? $existing->subtotal / 100 : null),
            'discount' => $request->input('discount', $existing && $existing->discount !== null ? $existing->discount / 100 : null),
            'tax' => $request->input('tax', $existing && $existing->tax !== null ? $existing->tax / 100 : null),
            'adjustment' => $request->input('adjustments', $existing && $existing->adjustments !== null ? $existing->adjustments / 100 : null),
            'total' => $request->input('total', $existing && $existing->total !== null ? $existing->total / 100 : null),
            'lead_id' => $lead?->id ?? $existing?->lead_id,
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id),
            'pipeline_stage_id' => $pipelineStage?->id ?? $existing?->pipeline_stage_id,
            'labels' => $labelIds,
            'products' => $products,
        ];

        return [$payload, $person, $organization];
    }

    private function buildProducts(array $lineItems, ?Quote $existing): ?array
    {
        if (empty($lineItems)) {
            return null;
        }

        return collect($lineItems)->map(function (array $item) use ($existing) {
            $productId = Product::where('external_id', $item['product_id'])->value('id');

            $quoteProductId = null;
            if ($existing && ! empty($item['id'])) {
                $quoteProductId = QuoteProduct::where('external_id', $item['id'])
                    ->where('quote_id', $existing->id)
                    ->value('id');
            }

            return [
                'id' => $productId,
                'product_id' => $productId,
                'quote_product_id' => $quoteProductId,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['amount'],
                'comments' => $item['comments'] ?? null,
            ];
        })->all();
    }
}
