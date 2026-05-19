<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\StoreOrderRequest;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpdateOrderRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\OrderResource;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\OrderService;

class OrderController extends ApiController
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()->with(['ownerUser', 'person', 'organization', 'labels', 'orderProducts.product']);

        if ($request->filled('user_owner_id')) {
            $query->where('user_owner_id', $request->input('user_owner_id'));
        }

        $query = $this->applySort(
            $query,
            $request,
            ['created_at', 'updated_at', 'total'],
            '-created_at'
        );

        $orders = $query->paginate($this->perPage($request))->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['ownerUser', 'person', 'organization', 'labels', 'orderProducts.product']);

        return new OrderResource($order);
    }

    public function store(StoreOrderRequest $request)
    {
        $this->authorize('create', Order::class);

        [$payload, $person, $organization] = $this->buildPayload($request);

        $order = $this->orderService->create($payload, $person, $organization);

        $order->refresh()->load(['ownerUser', 'person', 'organization', 'labels', 'orderProducts.product']);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $this->authorize('update', $order);

        [$payload, $person, $organization] = $this->buildPayload($request, $order);

        $this->orderService->update($payload, $order, $person, $organization);

        $order->refresh()->load(['ownerUser', 'person', 'organization', 'labels', 'orderProducts.product']);

        return new OrderResource($order);
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);

        $order->delete();

        return response()->noContent();
    }

    private function buildPayload(FormRequest $request, ?Order $existing = null): array
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

        $deal = $request->filled('deal_id')
            ? Deal::where('external_id', $request->input('deal_id'))->first()
            : null;

        $quote = $request->filled('quote_id')
            ? Quote::where('external_id', $request->input('quote_id'))->first()
            : null;

        $labelIds = collect($request->input('labels', []))
            ->map(fn ($uuid) => Label::where('external_id', $uuid)->value('id'))
            ->filter()
            ->values()
            ->all();

        $products = $this->buildProducts($request->input('line_items', []), $existing);

        $payload = (object) [
            'description' => $request->input('description', $existing?->description),
            'reference' => $request->input('reference', $existing?->reference),
            'currency' => $request->input('currency', $existing?->currency) ?: 'USD',
            'terms' => $request->input('terms', $existing?->terms),
            'sub_total' => $request->input('subtotal', $existing && $existing->subtotal !== null ? $existing->subtotal / 100 : null),
            'discount' => $request->input('discount', $existing && $existing->discount !== null ? $existing->discount / 100 : null),
            'tax' => $request->input('tax', $existing && $existing->tax !== null ? $existing->tax / 100 : null),
            'adjustment' => $request->input('adjustments', $existing && $existing->adjustments !== null ? $existing->adjustments / 100 : null),
            'total' => $request->input('total', $existing && $existing->total !== null ? $existing->total / 100 : null),
            'lead_id' => $lead?->id ?? $existing?->lead_id,
            'deal_id' => $deal?->id ?? $existing?->deal_id,
            'quote_id' => $quote?->id ?? $existing?->quote_id,
            'user_owner_id' => $request->input('user_owner_id', $existing?->user_owner_id),
            'labels' => $labelIds,
            'products' => $products,
            'addresses' => null,
        ];

        return [$payload, $person, $organization];
    }

    private function buildProducts(array $lineItems, ?Order $existing): ?array
    {
        if (empty($lineItems)) {
            return null;
        }

        return collect($lineItems)->map(function (array $item) use ($existing) {
            $productId = Product::where('external_id', $item['product_id'])->value('id');

            $orderProductId = null;
            if ($existing && ! empty($item['id'])) {
                $orderProductId = OrderProduct::where('external_id', $item['id'])
                    ->where('order_id', $existing->id)
                    ->value('id');
            }

            return [
                'id' => $productId,
                'product_id' => $productId,
                'order_product_id' => $orderProductId,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['amount'],
                'comments' => $item['comments'] ?? null,
            ];
        })->all();
    }
}
