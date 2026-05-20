# Laravel CRM REST API (v2)

A JSON REST API for partner developers integrating with `venturedrake/laravel-crm`. All requests
are authenticated with Laravel Sanctum personal access tokens.

- **Base URL:** `<your-app>/api/crm/v2`
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
   php artisan route:list --path=api/crm
   ```

   You should see 8 resourceful entities (`leads`, `products`, `organizations`, `people`, `deals`,
   `quotes`, `orders`, `invoices`) × 5 verbs each, plus 3 auth routes (`POST auth/token`,
   `GET auth/me`, `DELETE auth/token`).

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
POST /api/crm/v2/auth/token
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
GET /api/crm/v2/leads HTTP/1.1
Authorization: Bearer 1|abcdef1234...
Accept: application/json
```

### Inspecting and revoking the current token

```http
GET    /api/crm/v2/auth/me      → 200 { id, name, email }
DELETE /api/crm/v2/auth/token   → 204
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
| `POST` | `/api/crm/v2/auth/token` | Issue a token. Public (no auth required). |
| `GET` | `/api/crm/v2/auth/me` | Return the authenticated user. |
| `DELETE` | `/api/crm/v2/auth/token` | Revoke the current token. |

### Entities

| Resource | Path | Notable fields |
|---|---|---|
| Lead | `/api/crm/v2/leads` | `title`, `description`, `amount`, `currency`, `expected_close`, `person_id`, `organization_id`, `lead_source_id`, `pipeline_stage_id`, `labels[]`, `user_owner_id` |
| Product | `/api/crm/v2/products` | `name`, `code`, `description`, `unit_price`, `currency`, `tax_rate`, `tax_rate_id`, `product_category_id`, `active`, `user_owner_id` |
| Organization | `/api/crm/v2/organizations` | `name`, `website`, `email`, `phone`, `annual_revenue`, `total_money_raised`, `number_of_employees`, `industry_id`, `organization_type_id`, `timezone_id`, `labels[]`, `user_owner_id` |
| Person | `/api/crm/v2/people` | `first_name`, `last_name`, `gender`, `birthday`, `description`, `organization_id`, `labels[]`, `user_owner_id` |
| Deal | `/api/crm/v2/deals` | `title`, `description`, `amount`, `currency`, `expected_close`, `lead_id`, `person_id`, `organization_id`, `pipeline_stage_id`, `labels[]`, `user_owner_id` |
| Quote | `/api/crm/v2/quotes` | `title`, `description`, `issue_at`, `expire_at`, `currency`, `sub_total`, `discount`, `tax`, `adjustment`, `total`, `person_id`, `organization_id`, `labels[]`, `line_items[]` |
| Order | `/api/crm/v2/orders` | `description`, `currency`, `sub_total`, `discount`, `tax`, `adjustment`, `total`, `person_id`, `organization_id`, `labels[]`, `line_items[]` |
| Invoice | `/api/crm/v2/invoices` | `reference`, `issue_date`, `due_date`, `currency`, `sub_total`, `discount`, `tax`, `adjustment`, `total`, `amount_due`, `amount_paid`, `person_id`, `organization_id`, `labels[]`, `line_items[]` |

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

### Nested line items (Quote / Order / Invoice)

The `line_items` array is accepted on `POST` and `PUT`. Each item has the following shape:

```json
{
  "id": "8f1a...optional-uuid-for-existing-line",
  "product_id": "44d4...product-uuid",
  "quantity": 2,
  "unit_price": 100.00,
  "amount": 200.00,
  "comments": "Optional notes"
}
```

- **Create:** omit `id`. A new line is inserted.
- **Update in place:** include the existing line's `id` (UUID). The line is updated.
- **Replace lines:** omit `id` on every line in a `PUT`. Existing lines not matched in the
  payload are deleted.

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

---

## Worked example

Issue a token, list leads, create a lead, then revoke the token.

```bash
# 1. Issue a token
curl -s -X POST https://example.test/api/crm/v2/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","device_name":"curl"}' \
  | jq .

# 2. List leads
TOKEN="1|abcdef..."
curl -s https://example.test/api/crm/v2/leads \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | jq .

# 3. Create a lead
curl -s -X POST https://example.test/api/crm/v2/leads \
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
curl -s -X DELETE https://example.test/api/crm/v2/auth/token \
  -H "Authorization: Bearer $TOKEN" \
  -i
```
