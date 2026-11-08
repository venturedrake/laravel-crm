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
    // The header tax is the lines' tax at the product's rate; the posted
    // `tax` is ignored.
    $product->update(['tax_rate' => 10]);

    $response = $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'reference' => 'INV-REF-1',
            'currency' => 'USD',
            'tax' => 99.00,
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
    expect($payload['tax'])->toEqual(30.00);
    expect($payload['total'])->toEqual(330.00);
    expect($payload['line_items'])->toHaveCount(1);
    expect($payload['line_items'][0]['product_id'])->toBe($product->external_id);
    expect($payload['line_items'][0]['quantity'])->toEqual(3.0);
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
    expect($response->json('data.line_items.0.quantity'))->toEqual(5.0);

    $stored = InvoiceLine::where('external_id', $lineId)->first();
    expect($stored->quantity)->toBe(5.0);
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

/*
 * Decimal quantities - see the matching block in QuoteApiTest.
 */

test('POST /invoices accepts a fractional line item quantity', function () {
    $user = invoiceApiUser();
    $product = createInvoiceApiProduct('By weight');

    $response = $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'currency' => 'USD',
            'line_items' => [
                ['product_id' => $product->external_id, 'quantity' => 3.5, 'unit_price' => 1.99, 'amount' => 6.97],
            ],
        ]);

    $response->assertStatus(201);

    expect($response->json('data.line_items.0.quantity'))->toBe(3.5);

    $stored = Invoice::where('external_id', $response->json('data.id'))->first();
    expect($stored->invoiceLines->first()->quantity)->toBe(3.5);
});

test('POST /invoices returns 422 for a quantity finer than 3 decimal places', function () {
    $user = invoiceApiUser();
    $product = createInvoiceApiProduct('By weight');

    $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'currency' => 'USD',
            'line_items' => [
                ['product_id' => $product->external_id, 'quantity' => 3.5555, 'unit_price' => 1.99, 'amount' => 7.09],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.quantity']);
});

test('POST /invoices returns 422 for a zero quantity', function () {
    $user = invoiceApiUser();
    $product = createInvoiceApiProduct('By weight');

    $this->withHeaders(invoiceApiHeaders($user))
        ->postJson('/crm/api/v2/invoices', [
            'currency' => 'USD',
            'line_items' => [
                ['product_id' => $product->external_id, 'quantity' => 0, 'unit_price' => 1.99, 'amount' => 0],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['line_items.0.quantity']);
});

describe('the due date', function () {
    beforeEach(function () {
        $this->withHeaders(invoiceApiHeaders(invoiceApiUser()));
        $this->organization = Organization::create(['name' => 'Acme Co']);
    });

    it('is rejected on a day before the issue date', function () {
        $this->postJson('/crm/api/v2/invoices', [
            'currency' => 'USD',
            'organization_id' => $this->organization->external_id,
            'issue_date' => '2026-11-08T00:00:00Z',
            'due_date' => '2026-11-01T00:00:00Z',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['due_date'])
            ->assertJsonMissingValidationErrors(['issue_date']);

        expect(Invoice::count())->toBe(0);
    });

    it('is accepted on the same day at an earlier time', function () {
        $this->postJson('/crm/api/v2/invoices', [
            'currency' => 'USD',
            'organization_id' => $this->organization->external_id,
            'issue_date' => '2026-11-08T14:23:00Z',
            'due_date' => '2026-11-08T00:00:00Z',
        ])->assertCreated();
    });

    it('is checked against the stored issue date when sent alone', function () {
        $invoice = Invoice::create(['currency' => 'USD', 'issue_date' => '2026-11-08', 'due_date' => '2026-11-30']);

        $this->putJson('/crm/api/v2/invoices/'.$invoice->external_id, ['due_date' => '2026-11-01T00:00:00Z'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);

        expect($invoice->fresh()->due_date->toDateString())->toBe('2026-11-30');
    });

    it('puts the error on the issue date when only that is sent', function () {
        $invoice = Invoice::create(['currency' => 'USD', 'issue_date' => '2026-11-01', 'due_date' => '2026-11-08']);

        $this->putJson('/crm/api/v2/invoices/'.$invoice->external_id, ['issue_date' => '2026-11-09T00:00:00Z'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['issue_date'])
            ->assertJsonMissingValidationErrors(['due_date']);

        expect($invoice->fresh()->issue_date->toDateString())->toBe('2026-11-01');
    });

    it('does not stop an unrelated update to an invoice with no dates', function () {
        $invoice = Invoice::create(['currency' => 'USD', 'issue_date' => null, 'due_date' => null]);

        $this->putJson('/crm/api/v2/invoices/'.$invoice->external_id, ['reference' => 'Changed'])
            ->assertOk();

        expect($invoice->fresh()->reference)->toBe('Changed');
    });

    it('does not stop an unrelated update to an invoice already stored out of order', function () {
        $invoice = Invoice::create(['currency' => 'USD', 'issue_date' => '2026-11-08', 'due_date' => '2026-11-01']);

        $this->putJson('/crm/api/v2/invoices/'.$invoice->external_id, ['reference' => 'Changed'])
            ->assertOk();

        expect($invoice->fresh()->reference)->toBe('Changed');
    });

    it('can be cleared whatever the issue date', function () {
        $invoice = Invoice::create(['currency' => 'USD', 'issue_date' => '2026-11-08', 'due_date' => '2026-11-30']);

        $this->putJson('/crm/api/v2/invoices/'.$invoice->external_id, ['due_date' => null])
            ->assertOk();
    });
});
