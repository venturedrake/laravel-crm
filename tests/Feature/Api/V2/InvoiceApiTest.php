<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function invoiceApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Invoice API User',
        'email' => 'invoice-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function invoiceApiHeaders(User $user): array
{
    $token = $user->createToken('invoice-api-test')->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

function createInvoiceApiProduct(string $name = 'Widget'): Product
{
    return Product::create(['name' => $name])->refresh();
}

test('GET /invoices returns 401 when unauthenticated', function () {
    $this->getJson('/crm/api/v2/invoices')->assertStatus(401);
});

test('POST /invoices creates an invoice with nested line items', function () {
    $user = invoiceApiUser();
    $organization = Organization::create(['name' => 'Acme Co']);
    $product = createInvoiceApiProduct();

    $response = $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'reference' => 'INV-REF-1',
            'currency' => 'USD',
            'subtotal' => 300.00,
            'tax' => 30.00,
            'total' => 330.00,
            'issue_date' => '2026-05-19T00:00:00Z',
            'due_date' => '2026-06-19T00:00:00Z',
            'organization_id' => $organization->external_id,
            'user_owner_id' => $user->id,
            'line_items' => [
                [
                    'product_id' => $product->external_id,
                    'quantity' => 3,
                    'unit_price' => 100.00,
                    'amount' => 300.00,
                    'comments' => 'Consulting hours',
                ],
            ],
        ]);

    $response->assertStatus(201);

    $payload = $response->json('data');
    expect(Str::isUuid($payload['id']))->toBeTrue();
    expect($payload['invoice_id'])->toBeString();
    expect($payload['reference'])->toBe('INV-REF-1');
    expect($payload['subtotal'])->toEqual(300.00);
    expect($payload['total'])->toEqual(330.00);
    expect($payload['line_items'])->toHaveCount(1);
    expect($payload['line_items'][0]['product_id'])->toBe($product->external_id);
    expect($payload['line_items'][0]['quantity'])->toBe(3);
    expect($payload['line_items'][0]['amount'])->toEqual(300.00);

    $stored = Invoice::where('external_id', $payload['id'])->first();
    expect((int) $stored->total)->toBe(33000);
    expect($stored->invoiceLines)->toHaveCount(1);
    expect((int) $stored->invoiceLines->first()->amount)->toBe(30000);
});

test('POST /invoices returns 422 when issue_date is not ISO-8601', function () {
    $user = invoiceApiUser();

    $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'issue_date' => '05/19/2026',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['issue_date']);
});

test('POST /invoices returns 422 when line item product UUID is unknown', function () {
    $user = invoiceApiUser();

    $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'line_items' => [[
                'product_id' => (string) Str::uuid(),
                'quantity' => 1,
                'unit_price' => 10.00,
                'amount' => 10.00,
            ]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.product_id']);
});

test('POST /invoices returns 403 when policy denies create', function () {
    $user = invoiceApiUser(['crm_permissions' => json_encode([])]);

    $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [])
        ->assertStatus(403);
});

test('GET /invoices returns a paginated collection', function () {
    $user = invoiceApiUser();

    foreach (range(1, 3) as $i) {
        Invoice::create(['reference' => 'INV-'.$i]);
    }

    $response = $this->withHeaders(invoiceApiHeaders($user))
        ->getJson('/crm/api/v2/invoices?per_page=2');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.total'))->toBe(3);
});

test('PUT /invoices/{invoice} updates an existing invoice line in place', function () {
    $user = invoiceApiUser();
    $product = createInvoiceApiProduct();

    $created = $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'reference' => 'INV-A',
            'line_items' => [[
                'product_id' => $product->external_id,
                'quantity' => 1,
                'unit_price' => 50.00,
                'amount' => 50.00,
            ]],
        ])->json('data');

    $lineId = $created['line_items'][0]['id'];

    $response = $this->withHeaders(invoiceApiHeaders($user))
        ->putJson('/crm/api/v2/invoices/'.$created['id'], [
            'reference' => 'INV-A-updated',
            'line_items' => [[
                'id' => $lineId,
                'product_id' => $product->external_id,
                'quantity' => 5,
                'unit_price' => 50.00,
                'amount' => 250.00,
            ]],
        ]);

    $response->assertOk();
    expect($response->json('data.reference'))->toBe('INV-A-updated');
    expect($response->json('data.line_items'))->toHaveCount(1);
    expect($response->json('data.line_items.0.id'))->toBe($lineId);
    expect($response->json('data.line_items.0.quantity'))->toBe(5);

    $stored = InvoiceLine::where('external_id', $lineId)->first();
    expect((int) $stored->quantity)->toBe(5);
    expect((int) $stored->amount)->toBe(25000);
});

test('DELETE /invoices/{invoice} soft-deletes the invoice', function () {
    $user = invoiceApiUser();
    $invoice = Invoice::create(['reference' => 'Toast']);

    $this->withHeaders(invoiceApiHeaders($user))
        ->deleteJson('/crm/api/v2/invoices/'.$invoice->external_id)
        ->assertStatus(204);

    expect(Invoice::query()->where('external_id', $invoice->external_id)->exists())->toBeFalse();
    expect(Invoice::withTrashed()->where('external_id', $invoice->external_id)->exists())->toBeTrue();
});
