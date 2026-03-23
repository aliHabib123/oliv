# Design Document: Native Checkout

## Overview

The Native Checkout system extends the existing Oliv BFF to support end-to-end native payment flows via Tabby and Tamara. Rather than redirecting users to Shopify's hosted checkout, the mobile app initializes a PSP session server-side, presents the PSP's native SDK to the user, and the BFF verifies the payment before creating a Shopify order via the Admin GraphQL API. The system is multi-tenant by design, idempotent, and includes a fallback path to Shopify's hosted checkout URL.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Mobile App (iOS/Android)                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │ Payment UI   │  │ Tabby SDK    │  │ Tamara SDK / WebView     │  │
│  └──────┬───────┘  └──────┬───────┘  └────────────┬─────────────┘  │
└─────────┼────────────────┼──────────────────────────┼───────────────┘
          │ REST API        │ Native SDK               │ Native SDK
          ▼                 ▼                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        BFF (Laravel 12)                             │
│                                                                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ CheckoutSession  │  │ TabbyController  │  │ TamaraController │  │
│  │ Controller       │  │                  │  │                  │  │
│  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘  │
│           │                     │                      │            │
│  ┌────────▼─────────────────────▼──────────────────────▼─────────┐ │
│  │                     CheckoutSessionService                     │ │
│  └────────┬──────────────────────────────────────────────────────┘ │
│           │                                                         │
│  ┌────────▼──────────┐  ┌──────────────────┐  ┌─────────────────┐ │
│  │  TabbyService     │  │  TamaraService   │  │ ShopifyAdmin    │ │
│  │                   │  │                  │  │ Service         │ │
│  └────────┬──────────┘  └────────┬─────────┘  └────────┬────────┘ │
│           │                      │                      │          │
│  ┌────────▼──────────────────────▼──────────────────────▼────────┐ │
│  │                    Database (MySQL)                            │ │
│  │  checkout_sessions │ payment_transactions │ shopify_orders_   │ │
│  │                    │                      │ mapping           │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
          │                      │                      │
          ▼                      ▼                      ▼
  ┌───────────────┐   ┌──────────────────┐   ┌──────────────────────┐
  │ Tabby API     │   │ Tamara API       │   │ Shopify Admin API    │
  │ (tabby.ai)    │   │ (tamara.co)      │   │ (GraphQL)            │
  └───────────────┘   └──────────────────┘   └──────────────────────┘
          │                      │
          └──────────┬───────────┘
                     ▼
          ┌──────────────────────┐
          │  Webhook Endpoints   │
          │  /webhooks/tabby     │
          │  /webhooks/tamara    │
          └──────────────────────┘
```

### End-to-End Data Flow

1. App calls `GET /api/v1/payment-settings` → BFF returns tenant's enabled PSP configs (public fields only)
2. App calls `POST /api/v1/checkout/session` → BFF creates a `checkout_session` with locked amount, returns `session_id`
3. App calls `POST /api/v1/payments/tabby/initialize` or `tamara/initialize` → BFF calls PSP API, returns SDK token/URL
4. App presents PSP native SDK to user; user completes payment
5. PSP sends webhook to BFF → BFF validates HMAC, updates transaction + session status
6. App calls `POST /api/v1/checkout/confirm` → BFF re-verifies with PSP, creates Shopify order, returns order details
7. On any PSP failure → BFF returns `fallback_checkout_url` (Shopify hosted checkout)

---

## Components and Interfaces

### Controllers

| Controller | Responsibility |
|---|---|
| `CheckoutSessionController` | Create and confirm checkout sessions |
| `TabbyController` | Initialize Tabby payment, receive Tabby webhooks |
| `TamaraController` | Initialize Tamara payment, receive Tamara webhooks |

### Services

| Service | Responsibility |
|---|---|
| `CheckoutSessionService` | State machine enforcement, session CRUD, idempotency |
| `TabbyService` | Tabby API calls (create session, verify payment) |
| `TamaraService` | Tamara API calls (create order, verify payment) |
| `ShopifyAdminService` | Shopify Admin GraphQL: orderCreate, orderMarkAsPaid |
| `WebhookVerificationService` | HMAC signature validation for PSP webhooks |

### Existing Services (reused)

- `ShopifyStorefrontService` — cart creation and checkout URL retrieval for fallback
- `ResolveTenantFromAppKey` middleware — tenant resolution on all API routes

---

## Data Models

### checkout_sessions

```sql
CREATE TABLE checkout_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    session_uuid    CHAR(36) NOT NULL UNIQUE,          -- client-facing ID
    idempotency_key CHAR(36) NOT NULL,
    status          ENUM(
                        'pending',
                        'payment_initiated',
                        'awaiting_confirmation',
                        'authorized',
                        'captured',
                        'order_created',
                        'failed',
                        'expired'
                    ) NOT NULL DEFAULT 'pending',
    psp_type        VARCHAR(32) NULL,                  -- 'tabby' | 'tamara'
    amount          DECIMAL(12,2) NOT NULL,
    currency        CHAR(3) NOT NULL,
    customer_email  VARCHAR(255) NOT NULL,
    customer_phone  VARCHAR(32) NOT NULL,
    shipping_address JSON NOT NULL,
    line_items      JSON NOT NULL,                     -- snapshot, immutable after creation
    shopify_cart_id VARCHAR(255) NULL,
    fallback_url    TEXT NULL,                         -- Shopify hosted checkout URL
    failure_reason  TEXT NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_session_uuid (session_uuid),
    INDEX idx_idempotency (tenant_id, idempotency_key),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### payment_transactions

```sql
CREATE TABLE payment_transactions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    checkout_session_id BIGINT UNSIGNED NOT NULL,
    psp_type            VARCHAR(32) NOT NULL,           -- 'tabby' | 'tamara'
    psp_transaction_id  VARCHAR(255) NULL,              -- PSP's own ID
    psp_session_data    JSON NULL,                      -- raw PSP response
    status              ENUM(
                            'pending',
                            'authorized',
                            'captured',
                            'failed',
                            'cancelled'
                        ) NOT NULL DEFAULT 'pending',
    amount              DECIMAL(12,2) NOT NULL,
    currency            CHAR(3) NOT NULL,
    idempotency_key     CHAR(36) NOT NULL,
    failure_reason      TEXT NULL,
    authorized_at       TIMESTAMP NULL,
    captured_at         TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tenant (tenant_id),
    INDEX idx_session (checkout_session_id),
    INDEX idx_psp_transaction (psp_type, psp_transaction_id),
    INDEX idx_idempotency (tenant_id, idempotency_key),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (checkout_session_id) REFERENCES checkout_sessions(id)
);
```

### shopify_orders_mapping

```sql
CREATE TABLE shopify_orders_mapping (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    checkout_session_id BIGINT UNSIGNED NOT NULL UNIQUE,
    payment_transaction_id BIGINT UNSIGNED NOT NULL,
    shopify_order_id    VARCHAR(255) NOT NULL,          -- Shopify GID
    shopify_order_name  VARCHAR(64) NULL,               -- e.g. #1001
    shopify_order_data  JSON NULL,                      -- raw order response
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tenant (tenant_id),
    INDEX idx_shopify_order (tenant_id, shopify_order_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (checkout_session_id) REFERENCES checkout_sessions(id),
    FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id)
);
```

### Existing tables leveraged

- `tenants` — tenant identity and Shopify credentials
- `payment_gateways` — per-tenant PSP config (already has encrypted `config` column)

---

## API Contracts

### GET /api/v1/payment-settings
*(existing endpoint, no change to contract)*

**Response 200:**
```json
{
  "ok": true,
  "payment_settings": {
    "currency_code": "AED",
    "country_code": "AE",
    "payment_methods": [
      { "type": "tabby", "display_name": "Tabby", "is_test_mode": true, "config": { "public_key": "pk_test_xxx" } },
      { "type": "tamara", "display_name": "Tamara", "is_test_mode": false, "config": { "merchant_url": "https://..." } }
    ],
    "fallback_checkout_url": null
  }
}
```

---

### POST /api/v1/checkout/session

**Headers:** `X-App-Key`, `Idempotency-Key` (UUID)

**Request:**
```json
{
  "line_items": [{ "variant_id": "gid://shopify/ProductVariant/123", "quantity": 2 }],
  "customer": { "email": "[email]", "phone": "+971501234567", "first_name": "John", "last_name": "Doe" },
  "shipping_address": {
    "first_name": "John", "last_name": "Doe",
    "address1": "123 Main St", "city": "Dubai",
    "country_code": "AE", "zip": "00000", "phone": "+971501234567"
  }
}
```

**Response 201:**
```json
{
  "ok": true,
  "session_id": "uuid-v4",
  "amount": "299.00",
  "currency": "AED",
  "expires_at": "2026-03-18T11:30:00Z"
}
```

**Response 409** (duplicate idempotency key): returns original 201 body.
**Response 422**: field-level validation errors.

---

### POST /api/v1/payments/tabby/initialize

**Headers:** `X-App-Key`, `Idempotency-Key`

**Request:**
```json
{ "session_id": "uuid-v4" }
```

**Response 200:**
```json
{
  "ok": true,
  "payment_id": "tabby_pay_xxx",
  "web_url": "https://checkout.tabby.ai/...",
  "psp_type": "tabby"
}
```

**Response 409**: session not in `pending` state.
**Response 502**: Tabby API error (includes `fallback_checkout_url`).

---

### POST /api/v1/payments/tamara/initialize

**Headers:** `X-App-Key`, `Idempotency-Key`

**Request:**
```json
{ "session_id": "uuid-v4" }
```

**Response 200:**
```json
{
  "ok": true,
  "order_id": "tamara_order_xxx",
  "checkout_url": "https://checkout.tamara.co/...",
  "psp_type": "tamara"
}
```

**Response 409**: session not in `pending` state.
**Response 502**: Tamara API error (includes `fallback_checkout_url`).

---

### POST /api/v1/checkout/confirm

**Headers:** `X-App-Key`, `Idempotency-Key`

**Request:**
```json
{ "session_id": "uuid-v4" }
```

**Response 200:**
```json
{
  "ok": true,
  "shopify_order_id": "gid://shopify/Order/987654321",
  "shopify_order_name": "#1001",
  "status": "order_created"
}
```

**Response 402**: payment not authorized.
**Response 202**: payment verified but Shopify order creation queued (retry in progress).
**Response 409**: session already confirmed (returns existing order ID).

---

### POST /webhooks/tabby

**Headers:** `X-Tabby-Signature` (HMAC-SHA256)

**Request:** Tabby webhook payload (payment authorized/failed/cancelled)

**Response 200**: always, after processing.
**Response 401**: invalid signature.

---

### POST /webhooks/tamara

**Headers:** `X-Tamara-Signature` (HMAC-SHA256)

**Request:** Tamara webhook payload

**Response 200**: always, after processing.
**Response 401**: invalid signature.

---

## Shopify Admin GraphQL Integration

### orderCreate — create a draft order after payment

```graphql
mutation orderCreate($order: OrderCreateOrderInput!, $options: OrderCreateOptionsInput) {
  orderCreate(order: $order, options: $options) {
    order {
      id
      name
      totalPriceSet { shopMoney { amount currencyCode } }
    }
    userErrors { field message }
  }
}
```

Variables built from the `checkout_session` line items + shipping address + customer info.

### orderMarkAsPaid — mark the order paid after PSP verification

```graphql
mutation orderMarkAsPaid($input: OrderMarkAsPaidInput!) {
  orderMarkAsPaid(input: $input) {
    order { id financialStatus }
    userErrors { field message }
  }
}
```

Called immediately after `orderCreate` succeeds, using the Shopify order ID.

### When to use each

| Mutation | When |
|---|---|
| `orderCreate` | After PSP payment is verified as `authorized` |
| `orderMarkAsPaid` | Immediately after `orderCreate` succeeds |
| `orderCreateManualPayment` | Not used — `orderMarkAsPaid` is sufficient for our flow |

---

## State Machine

```
                    ┌─────────┐
                    │ pending │ ◄── initial state on session creation
                    └────┬────┘
                         │ PSP initialize called
                         ▼
               ┌──────────────────┐
               │ payment_initiated│
               └────────┬─────────┘
                        │ PSP SDK interaction complete
                        ▼
            ┌───────────────────────┐
            │ awaiting_confirmation │ ◄── webhook received OR app polls
            └───────────┬───────────┘
                        │ PSP confirms authorized
                        ▼
                  ┌──────────┐
                  │authorized│
                  └────┬─────┘
                       │ /checkout/confirm called + Shopify order created
                       ▼
                  ┌──────────┐
                  │ captured │
                  └────┬─────┘
                       │ Shopify order confirmed
                       ▼
               ┌───────────────┐
               │ order_created │ ◄── terminal success state
               └───────────────┘

  Any state ──► failed    (PSP error, verification failure, timeout)
  payment_initiated ──► expired  (scheduled job after 30 min)
```

**Invalid transitions** return HTTP 409 and are logged.

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Property 1: Payment settings expose only public fields
*For any* tenant with configured payment gateways, the `GET /api/v1/payment-settings` response must contain zero private fields (e.g., `secret_key`, `api_token`) for any payment method in the response.
**Validates: Requirements 1.3**

---

Property 2: Checkout session amount is immutable after creation
*For any* checkout session, the `amount` and `currency` stored at creation time must equal the `amount` and `currency` used when calling the PSP initialization API, regardless of what values the client sends in the initialization request body.
**Validates: Requirements 2.4, 3.5, 4.5**

---

Property 3: Idempotency key deduplication
*For any* POST request with an `Idempotency-Key` header, submitting the same key more than once within 24 hours must return the same response body and HTTP status code as the first successful response, without creating duplicate database records.
**Validates: Requirements 2.2, 2.3, 9.1, 9.3**

---

Property 4: State machine only allows valid transitions
*For any* checkout session in state S, attempting a transition to a state not in the valid successor set of S must result in a 409 response and leave the session state unchanged.
**Validates: Requirements 10.1, 10.2**

---

Property 5: Shopify order is never created without verified payment
*For any* checkout session, a `shopify_orders_mapping` record must not exist unless the corresponding `payment_transaction` has status `authorized` or `captured`.
**Validates: Requirements 6.2, 6.3**

---

Property 6: Webhook idempotency — terminal state is not reprocessed
*For any* `payment_transaction` already in a terminal state (`captured` or `failed`), receiving a webhook for that transaction must not change the transaction status and must return HTTP 200.
**Validates: Requirements 5.5**

---

Property 7: Tenant data isolation
*For any* two tenants A and B, a request authenticated as tenant A must never return or modify checkout sessions, payment transactions, or order mappings belonging to tenant B.
**Validates: Requirements 7.1, 7.2**

---

Property 8: Duplicate Shopify order prevention
*For any* checkout session, calling `POST /api/v1/checkout/confirm` multiple times must result in exactly one `shopify_orders_mapping` record and must return the same Shopify order ID on all calls after the first.
**Validates: Requirements 6.5, 9.2**

---

## Error Handling

| Scenario | HTTP Status | Behavior |
|---|---|---|
| Missing / invalid `X-App-Key` | 404 | Existing middleware handles this |
| Validation failure | 422 | Field-level errors returned |
| Session not in expected state | 409 | State + attempted transition logged |
| PSP API unreachable / 5xx | 502 | Log error, return `fallback_checkout_url` |
| PSP payment not authorized | 402 | Do not create Shopify order |
| Shopify order creation fails | 202 | Enqueue `CreateShopifyOrderJob`, return 202 |
| Invalid webhook signature | 401 | Log attempt, discard payload |
| Duplicate idempotency key | 200/201 | Return cached response |
| Session expired | 410 | Return 410 Gone |

---

## Testing Strategy

### Unit Testing (PHPUnit)

- `CheckoutSessionService` state machine transitions — assert valid transitions succeed and invalid ones throw
- `TabbyService` / `TamaraService` — mock HTTP client, assert correct request shape and response mapping
- `ShopifyAdminService` — mock GraphQL client, assert mutation variables are built correctly from session data
- `WebhookVerificationService` — assert valid HMAC passes, tampered payload fails
- `PaymentGateway::getPublicConfig()` — assert no private fields leak (existing model method)

### Property-Based Testing (PHPUnit + [eris/eris](https://github.com/giorgiosironi/eris))

We will use **eris/eris** as the property-based testing library. Each property test runs a minimum of 100 iterations.

Each property-based test MUST be tagged with the following comment format:
`// Feature: native-checkout, Property {N}: {property_text}`

**Property 1** — Payment settings expose only public fields
Generate random `PaymentGateway` instances with arbitrary config arrays containing both public and private fields. Assert that `getPublicConfig()` never returns any key listed in `getPrivateFields()`.

**Property 2** — Checkout session amount is immutable after creation
Generate random valid session creation payloads. Create the session, then call initialize with a different amount in the body. Assert the PSP is called with the session's stored amount, not the request body amount.

**Property 3** — Idempotency key deduplication
Generate random valid session payloads with a fixed idempotency key. Call the endpoint N times (N ∈ [2,10]). Assert exactly one database record exists and all responses are identical.

**Property 4** — State machine only allows valid transitions
Generate random (current_state, target_state) pairs. Assert that only pairs in the valid transition map succeed; all others return 409.

**Property 5** — Shopify order never created without verified payment
Generate checkout sessions in various non-authorized states. Assert that calling confirm returns non-200 and no `shopify_orders_mapping` record is created.

**Property 6** — Webhook idempotency
Generate random webhook payloads for transactions already in terminal states. Assert the transaction status is unchanged after processing and HTTP 200 is returned.

**Property 7** — Tenant data isolation
Generate two tenants with overlapping session UUIDs. Assert that requests authenticated as tenant A never return data belonging to tenant B.

**Property 8** — Duplicate Shopify order prevention
Generate a confirmed session. Call confirm N times (N ∈ [2,5]). Assert exactly one `shopify_orders_mapping` row exists.
