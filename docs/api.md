# Laravel CRM REST API (v2)

A JSON REST API for partner developers integrating with `venturedrake/laravel-crm`. All requests
are authenticated with Laravel Sanctum personal access tokens.

- **Base URL:** `<your-app>/crm/api/v2`
- **Content type:** `application/json`
- **Auth:** Sanctum bearer tokens

---

## Install

The API ships with the package; you only need to wire Sanctum into the host application.

1. **Publish and run Sanctum migrations** (creates the `personal_access_tokens` table):

   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   php artisan migrate
   ```

2. **Add `HasApiTokens` to your host `User` model:**

   ```php
   use Laravel\Sanctum\HasApiTokens;

   class User extends Authenticatable
   {
       use HasApiTokens;
       // ...
   }
   ```

3. **Confirm the API routes load.** After installing the package and running migrations, run:

   ```bash
   php artisan route:list --path=crm/api
   ```

   You should see 8 resourceful entities (`leads`, `products`, `organizations`, `people`, `deals`,
   `quotes`, `orders`, `invoices`) × 5 verbs each, the 5 nested
   [product variation](#product-variations) routes (`products/{product}/variations`), plus 3
   auth routes (`POST auth/token`, `GET auth/me`, `DELETE auth/token`).

4. **Issue a token via the ops command** (no controller round-trip required):

   ```bash
   php artisan laravel-crm:api-token user@example.com --name="Mobile App"
   ```

   The plaintext token is printed once — copy it and store securely. The command exits non-zero
   if the user does not exist or lacks `crm_access`.

---

## Authentication

The API uses Sanctum personal access tokens. There are two ways to obtain one:

### Option A — Issue via the API

```http
POST /crm/api/v2/auth/token
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "Mobile App"
}
```

**Response (201):**

```json
{
  "token": "1|abcdef1234...",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "user@example.com"
  }
}
```

- Returns `422` on bad credentials, an unknown email, or when the user lacks `crm_access`. The
  response is intentionally indistinguishable across these cases to avoid leaking which emails
  belong to real users.
- `device_name` is optional; defaults to the request's `User-Agent` or `api-token`.

### Option B — Issue via artisan (ops use)

```bash
php artisan laravel-crm:api-token user@example.com --name="Mobile App"
```

### Authenticated requests

Pass the token in the `Authorization` header:

```http
GET /crm/api/v2/leads HTTP/1.1
Authorization: Bearer 1|abcdef1234...
Accept: application/json
```

### Inspecting and revoking the current token

```http
GET    /crm/api/v2/auth/me      → 200 { "user": { id, name, email } }
DELETE /crm/api/v2/auth/token   → 204
```

---

## Headers

| Header | Required | Purpose |
|---|---|---|
| `Authorization: Bearer <token>` | Yes (except `POST /auth/token`) | Sanctum personal access token. |
| `Accept: application/json` | Recommended | The `laravel-crm.api.json` middleware forces JSON responses; this header is set automatically by Laravel when missing. |
| `Content-Type: application/json` | Yes (for `POST`/`PUT`) | Request body is JSON. |
| `X-Team-ID: <team-id>` | Optional | Overrides the authenticated user's active team for this request. Must be a team the user belongs to (per `User::allTeams()`); otherwise the API returns `403`. Only relevant when `laravel-crm.teams=true`. |

### Multi-tenancy notes

When the host app runs in teams mode (`config('laravel-crm.teams', true)`):

- Without `X-Team-ID`, requests are scoped to the user's `current_team_id`.
- With `X-Team-ID`, the request runs in the context of that team for list/store/update/delete
  endpoints. `GET /{resource}/{uuid}` resolves the route-bound model using the user's *default*
  current team because Laravel's `SubstituteBindings` runs before `SetApiTeamContext`. Use the
  list endpoints (filtered by `X-Team-ID`) to discover the correct UUIDs for the active team.
- **A token whose user has no current team cannot reference anything.** Every FK on a write
  whose table is team-scoped (`person_id`, `organization_id`, `pipeline_stage_id`, `labels[]`,
  `line_items.*.product_id`, …) is validated against the active team. When teams are on and
  the token's user has no `currentTeam` — and none was supplied via `X-Team-ID` — the rule
  matches nothing rather than falling back to unscoped, so **every** such id comes back
  `422` at once. It
  presents as "all my ids are suddenly invalid". The fix is on the user record, not the
  payload: give the user a current team, or send `X-Team-ID`. Service accounts created outside
  the host app's normal registration flow are the usual cause.

---

## Endpoint matrix

All entity endpoints follow the same shape:

| Verb | Path | Action |
|---|---|---|
| `GET` | `/{resource}` | List (paginated). |
| `POST` | `/{resource}` | Create. |
| `GET` | `/{resource}/{uuid}` | Show. |
| `PUT` | `/{resource}/{uuid}` | Update. |
| `DELETE` | `/{resource}/{uuid}` | Soft-delete. |

The `{uuid}` in URIs is the entity's `external_id` (UUID), exposed as `id` in JSON responses.

### Auth

| Method | Path | Notes |
|---|---|---|
| `POST` | `/crm/api/v2/auth/token` | Issue a token. Public (no auth required). |
| `GET` | `/crm/api/v2/auth/me` | Return the authenticated user. |
| `DELETE` | `/crm/api/v2/auth/token` | Revoke the current token. |

### Entities

| Resource | Path | Notable fields |
|---|---|---|
| Lead | `/crm/api/v2/leads` | `title`, `description`, `amount`, `currency`, `expected_close`, `person_id`, `organization_id`, `lead_source_id`, `pipeline_stage_id`, `labels[]`, `user_owner_id` |
| Product | `/crm/api/v2/products` | `name`, `code`, `description`, `unit_price`, `currency`, `tax_rate`, `tax_rate_id`, `product_category_id`, `active`, `user_owner_id`; responses also carry `variations[]` (read-only here — see [Product variations](#product-variations)) |
| Organization | `/crm/api/v2/organizations` | `name`, `website`, `email`, `phone`, `annual_revenue`, `total_money_raised`, `number_of_employees`, `industry_id`, `organization_type_id`, `timezone_id`, `labels[]`, `user_owner_id` |
| Person | `/crm/api/v2/people` | `first_name`, `last_name`, `gender`, `birthday`, `description`, `organization_id`, `labels[]`, `user_owner_id` |
| Deal | `/crm/api/v2/deals` | `title`, `description`, `amount`, `currency`, `expected_close`, `lead_id`, `person_id`, `organization_id`, `pipeline_stage_id`, `labels[]`, `user_owner_id` |
| Quote | `/crm/api/v2/quotes` | `title`, `description`, `reference`, `issue_at`, `expire_at`, `currency`, `terms`, `discount`, `tax`, `adjustments`, `person_id`, `organization_id`, `lead_id`, `pipeline_stage_id`, `labels[]`, `line_items[]` |
| Order | `/crm/api/v2/orders` | `reference`, `description`, `currency`, `terms`, `discount`, `tax`, `adjustments`, `person_id`, `organization_id`, `labels[]`, `line_items[]` |
| Invoice | `/crm/api/v2/invoices` | `reference`, `issue_date`, `due_date`, `currency`, `terms`, `tax`, `order_id`, `person_id`, `organization_id`, `labels[]`, `line_items[]` |

Quote / order / invoice responses additionally carry `subtotal` and `total` (and, on invoices,
`amount_due` / `amount_paid` / `fully_paid_at`). These are **computed, not writable** — see
[`subtotal` and `total`](#subtotal-and-total-are-computed-and-rejected-on-input) below.

### Conventions across all entity endpoints

- **IDs are UUIDs.** The JSON `id` is always the entity's `external_id`. Integer primary keys are
  never exposed. Lookup tables (lead source, pipeline stage, industry, etc.) accept integer IDs.
- **Money is dollars in JSON; cents in storage.** All amount/price/total fields are sent and
  returned as decimal dollars (e.g. `1500.50`). The package's model mutators multiply by 100 on
  write.
- **Timestamps are ISO-8601** with timezone offset, e.g. `2026-07-15T10:00:00+00:00` (`Z` UTC
  suffix is also accepted on input).
- **Pagination:** `?per_page=N` (1–100, default 25). Responses use Laravel's standard pagination
  envelope (`data`, `meta`, `links`).
- **Sorting:** `?sort=field` ascending; `?sort=-field` descending. Unknown columns are silently
  ignored. Default sort is `-created_at`.
- **Filtering:** `?user_owner_id=<int>` (and `?active=` on products) is supported on list
  endpoints. Other filters are documented per-resource as needed.
- **Soft deletes:** `DELETE` returns `204` and soft-deletes the row. Subsequent `GET`s return
  `404`.
- **Referenced ids must belong to the active team.** *(Changed in 2.4.0.)* Every UUID
  reference on a write is checked against the request's team as well as the table. An id
  belonging to another team is a `422` on that field, where it previously validated and
  produced a cross-team record. Single-tenant installs (`laravel-crm.teams = false`) are
  unaffected — the check is skipped entirely — as are package-wide lookup tables that carry
  no `team_id` column, which are still checked against the table alone.
- **`discount` and `tax` reject negatives.** *(Changed in 2.4.0.)* Both gained `min:0` on
  quote / order / invoice writes. A negative discount was a way to inflate a total; send an
  `adjustments` value instead.

### Product variations

A product can have variations (sizes, colours, …), each with an optional price of its own.
They are a sub-resource of the product:

| Method | Path | Permission | Notes |
|---|---|---|---|
| `GET` | `/crm/api/v2/products/{product}/variations` | `view crm products` | All the product's variations (active and inactive), ordered by `order`. Not paginated. |
| `POST` | `/crm/api/v2/products/{product}/variations` | `edit crm products` | Create. `201`. |
| `GET` | `/crm/api/v2/products/{product}/variations/{variation}` | `view crm products` | Show. |
| `PUT` / `PATCH` | `/crm/api/v2/products/{product}/variations/{variation}` | `edit crm products` | Update. Only the fields sent change. |
| `DELETE` | `/crm/api/v2/products/{product}/variations/{variation}` | `edit crm products` | Soft-delete. `204`. |

There are no variation permissions: reading a variation needs `view` on its product, and
creating, updating or deleting one needs `update` (edit) on the product. `{product}` and
`{variation}` are both UUIDs. The variation is looked up **within** the URL's product, so a
variation of another product — or a deleted one — is a `404`.

**Fields:**

| Field | Rules | Notes |
|---|---|---|
| `name` | required on `POST`; string, max 255 | |
| `code` | nullable string, max 255 | Shown on lines in place of the product code when set. |
| `barcode` | nullable string, max 255 | |
| `active` | boolean | Defaults to `true`. |
| `order` | nullable integer ≥ 0 | Defaults to after the last variation. |
| `unit_price` | nullable number ≥ 0, dollars | On `PUT`, omitting it leaves the price alone; sending `null` removes the variation's price in `currency`. |
| `currency` | nullable 3-letter code | The currency `unit_price` applies to. Defaults to the product's price currency. |

**Response:**

```json
{
  "data": {
    "id": "6b0e...variation-uuid",
    "name": "Large",
    "code": "WDG-L",
    "barcode": null,
    "active": true,
    "order": 2,
    "prices": [
      { "currency": "USD", "unit_price": 12.50 }
    ],
    "created_at": "2026-10-22T09:00:00+00:00",
    "updated_at": "2026-10-22T09:00:00+00:00"
  }
}
```

`GET /products` and `GET /products/{uuid}` return the same objects in a `variations` array.
The product's own `prices` never include variation prices.

**Price fallback:** a line on a variation with no price in the document currency uses the
product price.

**Product writes leave variations alone.** `POST` / `PUT` on `/products` neither accepts nor
changes variations; use the sub-resource above.

### Nested line items (Quote / Order / Invoice)

The `line_items` array is accepted on `POST` and `PUT`. Each item has the following shape:

```json
{
  "id": "8f1a...optional-uuid-for-existing-line",
  "product_id": "44d4...product-uuid",
  "product_variation_id": "6b0e...optional-variation-uuid",
  "quantity": 3.5,
  "unit_price": 100.00,
  "amount": 350.00,
  "comments": "Optional notes"
}
```

- **Create:** omit `id`. A new line is inserted.
- **Update in place:** include the existing line's `id` (UUID). The line is updated.
- **Replace lines:** omit `id` on every line in a `PUT`. Existing lines not matched in the
  payload are deleted.

#### `product_variation_id` (optional)

The UUID of one of `product_id`'s [variations](#product-variations). A variation of a
different product, or one from another team, is a `422` on
`line_items.N.product_variation_id`. Lines are replaced as a whole, so a line sent without
`product_variation_id` has no variation. A line already on a variation that has since been
deleted keeps it when re-sent with the same id; a new line cannot pick a deleted variation
(it is stored without one).

`unit_price` is still whatever you send — the API does not price the line for you.

Each line in a response carries `product_variation_id` and `variation`, both `null` when
the line has no variation:

```json
"product_variation_id": "6b0e...variation-uuid",
"variation": { "id": "6b0e...variation-uuid", "name": "Large", "code": "WDG-L" }
```

#### `quantity` accepts decimals (up to 3 places)

`quantity` is stored as `decimal(15,3)`, so a product sold by weight or volume can be
invoiced as `3.5` Kg or `0.25` L. The rule is `numeric`, `min:0.001`, `max:999999999`,
at most 3 decimal places — a value finer than that is a `422`, not a silent rounding.

`min:0.001` rather than `gt:0`: a quantity like `0.0001` would round to `0` on store and
the line would then be discarded without an error.

> **Breaking (response type).** `quantity` was previously cast to an integer in every
> response; it is now a JSON number and may come back fractional. Clients that decode it
> into an `int` field will truncate or fail. This is a widening on the request side —
> every payload that was valid before is still valid.

#### `subtotal` and `total` are computed, and rejected on input

*(Changed in 2.4.0.)* `subtotal` and `total` were accepted on quote / order / invoice
`POST` and `PUT` and are now derived from `line_items`, `discount`, `tax` and
`adjustments`. Sending either returns a `422` naming the cause:

```json
{
  "message": "The subtotal field is prohibited.",
  "errors": {
    "subtotal": ["The subtotal is calculated from line_items and can no longer be set on the request. Remove it from the payload."],
    "total": ["The total is calculated from line_items and can no longer be set on the request. Remove it from the payload."]
  }
}
```

They are rejected rather than ignored deliberately: a client posting its own authoritative
totals would otherwise get different numbers back with no error, and only notice when the
figures stopped reconciling. **Remove both fields from your write payloads.** Both are still
returned in responses, computed. A payload that omits them, or sends them as `null`, is
unaffected.

---

## Error format

The API returns standard Laravel error envelopes:

### `422 Unprocessable Entity` (validation)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "person_id": ["The selected person_id is invalid."]
  }
}
```

### `401 Unauthorized`

```json
{ "message": "Unauthenticated." }
```

### `403 Forbidden`

```json
{ "message": "This action is unauthorized." }
```

For the `X-Team-ID` non-member case:

```json
{ "message": "You are not a member of the requested team." }
```

### `404 Not Found`

Returned when a UUID does not resolve to a model (or has been soft-deleted).

```json
{ "message": "..." }
```

### `429 Too Many Requests`

Returned when the rate limit is exceeded. Standard Laravel headers include
`X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After`.

---

## Rate limits

The API enforces a single named rate limiter, `laravel-crm-api`:

| Caller | Limit |
|---|---|
| Authenticated (Sanctum) | **60 requests / minute / user** |
| Unauthenticated | **30 requests / minute / IP** |

Exceeding the limit returns `429 Too Many Requests` with `Retry-After` in seconds.

### `POST /auth/token` is throttled twice

*(Changed in 2.4.0.)* On top of the per-IP limit above, token issuing carries a fixed
`throttle:6,1` (6 attempts / minute / IP) and a **per-account** counter on failed attempts:

| Setting | Default | Env |
|---|---|---|
| `laravel-crm.api.token_attempts_per_account` | 5 | `LARAVEL_CRM_API_TOKEN_ATTEMPTS_PER_ACCOUNT` |
| `laravel-crm.api.token_attempts_decay_seconds` | 600 | `LARAVEL_CRM_API_TOKEN_ATTEMPTS_DECAY_SECONDS` |

The per-account counter is keyed on the submitted email, incremented only on a failed
attempt, and cleared on success. Once it trips, the endpoint returns `429` with the error
under `errors.email` rather than the usual `429` envelope:

```json
{
  "message": "Too many login attempts. Try again in 540 seconds.",
  "errors": { "email": ["Too many login attempts. Try again in 540 seconds."] }
}
```

**`POST /auth/token` could not return `429` before this release.** A client that re-issues a
token on every request, or retries on failure without backing off, will start seeing it —
issue a token once and reuse it, and back off on `429`. Both limits are per-IP or per-email,
so one misbehaving integration can lock out a shared address; raise the two config keys if
your deployment legitimately issues tokens in bursts.

---

## Worked example

Issue a token, list leads, create a lead, then revoke the token.

```bash
# 1. Issue a token
curl -s -X POST https://example.test/crm/api/v2/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","device_name":"curl"}' \
  | jq .

# 2. List leads
TOKEN="1|abcdef..."
curl -s https://example.test/crm/api/v2/leads \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | jq .

# 3. Create a lead
curl -s -X POST https://example.test/crm/api/v2/leads \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New enterprise lead",
    "amount": 12500.00,
    "currency": "USD",
    "expected_close": "2026-09-30T00:00:00Z"
  }' \
  | jq .

# 4. Revoke the token
curl -s -X DELETE https://example.test/crm/api/v2/auth/token \
  -H "Authorization: Bearer $TOKEN" \
  -i
```
