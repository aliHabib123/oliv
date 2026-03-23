# Requirements Document

## Introduction

The Native Checkout system is a production-grade, multi-tenant backend (BFF) that enables white-label mobile commerce apps to execute payments natively through external PSPs (Tabby and Tamara) without redirecting to Shopify's hosted checkout. The system dynamically serves payment configuration per tenant, manages checkout sessions, verifies payments server-side, and creates Shopify orders only after confirmed payment. It is designed to be idempotent, resilient to retries, and horizontally scalable.

## Glossary

- **BFF**: Backend For Frontend — the Laravel API layer serving the mobile app
- **Tenant**: A white-label store operator identified by `X-App-Key`
- **PSP**: Payment Service Provider — Tabby or Tamara
- **Checkout Session**: A server-side record tracking the lifecycle of a single checkout attempt
- **Payment Transaction**: A record of a single payment attempt against a PSP
- **Idempotency Key**: A client-supplied UUID that prevents duplicate operations on retry
- **Shopify Admin API**: GraphQL API used to create and manage orders server-side
- **Shopify Storefront API**: GraphQL API used to manage carts and retrieve checkout URLs
- **orderCreate**: Shopify Admin GraphQL mutation to create a draft order
- **orderCreateManualPayment**: Shopify Admin mutation to attach a manual payment to an order
- **orderMarkAsPaid**: Shopify Admin mutation to mark an order as fully paid
- **Native SDK**: PSP-provided mobile SDK that handles the payment UI natively in-app
- **Webhook**: HTTP callback from a PSP to the BFF notifying of payment status changes
- **State Machine**: Defined set of states and transitions for a checkout session lifecycle
- **HMAC**: Hash-based Message Authentication Code used to verify webhook authenticity

---

## Requirements

### Requirement 1

**User Story:** As a mobile app user, I want to see payment methods relevant to my tenant's configuration, so that I can choose how to pay without seeing irrelevant options.

#### Acceptance Criteria

1. WHEN a mobile app sends `GET /api/v1/payment-settings` with a valid `X-App-Key`, THE System SHALL return only the payment methods that are enabled and have valid configuration for that tenant.
2. WHEN a payment method has `is_test_mode` set to true, THE System SHALL include a `is_test_mode` flag in the response so the mobile app can display a test indicator.
3. WHEN a payment method's config contains sensitive server-side credentials (e.g., secret keys), THE System SHALL exclude those fields from the public API response and return only public-safe fields (e.g., `public_key`, `merchant_url`).
4. IF a tenant has no enabled payment methods, THEN THE System SHALL return an empty `payment_methods` array with `ok: true`.
5. WHILE the payment settings response is valid, THE System SHALL cache the result per tenant for a maximum of 60 minutes to reduce database load.

---

### Requirement 2

**User Story:** As a mobile app, I want to create a checkout session on the server before initiating payment, so that the server can track the full lifecycle of the transaction.

#### Acceptance Criteria

1. WHEN a mobile app sends `POST /api/v1/checkout/session` with valid line items, shipping address, and customer info, THE System SHALL create a `checkout_session` record with status `pending` and return a `session_id`.
2. WHEN a checkout session is created, THE System SHALL generate and store an idempotency key scoped to the tenant and session to prevent duplicate session creation.
3. IF a request is submitted with an idempotency key that matches an existing session for the same tenant, THEN THE System SHALL return the existing session response without creating a duplicate.
4. WHEN a checkout session is created, THE System SHALL calculate and store the total amount, currency, and line item snapshot so that the amount cannot be tampered with client-side during payment.
5. IF any required field (line items, shipping address, customer email, customer phone) is missing, THEN THE System SHALL return a 422 response with field-level validation errors.

---

### Requirement 3

**User Story:** As a mobile app, I want to initialize a Tabby payment for a checkout session, so that I can present the Tabby native SDK to the user.

#### Acceptance Criteria

1. WHEN a mobile app sends `POST /api/v1/payments/tabby/initialize` with a valid `session_id`, THE System SHALL call the Tabby Create Session API using the tenant's server-side Tabby secret key and return a `payment_id` and `web_url` to the mobile app.
2. WHEN the Tabby initialization succeeds, THE System SHALL update the checkout session status to `payment_initiated` and create a `payment_transaction` record with status `pending`.
3. IF the checkout session is not in `pending` status, THEN THE System SHALL return a 409 Conflict response and not call the Tabby API.
4. IF the Tabby API returns an error, THEN THE System SHALL log the error, set the payment transaction status to `failed`, and return a 502 response to the mobile app.
5. WHEN initializing Tabby, THE System SHALL use the amount and currency stored in the checkout session, not values supplied by the client in the request body.

---

### Requirement 4

**User Story:** As a mobile app, I want to initialize a Tamara payment for a checkout session, so that I can present the Tamara native SDK to the user.

#### Acceptance Criteria

1. WHEN a mobile app sends `POST /api/v1/payments/tamara/initialize` with a valid `session_id`, THE System SHALL call the Tamara Create Order API using the tenant's server-side Tamara token and return an `order_id` and `checkout_url` to the mobile app.
2. WHEN the Tamara initialization succeeds, THE System SHALL update the checkout session status to `payment_initiated` and create a `payment_transaction` record with status `pending`.
3. IF the checkout session is not in `pending` status, THEN THE System SHALL return a 409 Conflict response and not call the Tamara API.
4. IF the Tamara API returns an error, THEN THE System SHALL log the error, set the payment transaction status to `failed`, and return a 502 response to the mobile app.
5. WHEN initializing Tamara, THE System SHALL use the amount and currency stored in the checkout session, not values supplied by the client in the request body.

---

### Requirement 5

**User Story:** As the BFF, I want to receive and verify webhook callbacks from Tabby and Tamara, so that I can update payment status based on authoritative PSP events.

#### Acceptance Criteria

1. WHEN a webhook is received at `/webhooks/tabby` or `/webhooks/tamara`, THE System SHALL validate the request signature using HMAC before processing any payload data.
2. IF the webhook signature is invalid, THEN THE System SHALL return a 401 response and log the failed verification attempt without updating any records.
3. WHEN a webhook indicates a successful payment authorization, THE System SHALL update the corresponding `payment_transaction` status to `authorized` and the `checkout_session` status to `authorized`.
4. WHEN a webhook indicates a payment failure or cancellation, THE System SHALL update the `payment_transaction` status to `failed` and the `checkout_session` status to `failed`.
5. WHEN a webhook is received for a `payment_transaction` that is already in a terminal state (`captured`, `failed`), THE System SHALL return a 200 response without reprocessing to ensure idempotency.

---

### Requirement 6

**User Story:** As a mobile app, I want to confirm a checkout after payment authorization, so that the BFF can verify the payment and create the Shopify order.

#### Acceptance Criteria

1. WHEN a mobile app sends `POST /api/v1/checkout/confirm` with a valid `session_id`, THE System SHALL verify the payment status directly with the PSP API before proceeding.
2. WHEN PSP verification confirms the payment is authorized, THE System SHALL create a Shopify order using the Admin GraphQL API `orderCreate` mutation and mark it as paid using `orderMarkAsPaid`.
3. IF the PSP verification returns a non-authorized status, THEN THE System SHALL return a 402 Payment Required response and not create a Shopify order.
4. WHEN a Shopify order is successfully created, THE System SHALL store the Shopify order ID in the `shopify_orders_mapping` table and update the checkout session status to `order_created`.
5. IF `POST /api/v1/checkout/confirm` is called more than once for the same `session_id` that already has status `order_created`, THEN THE System SHALL return the existing Shopify order ID without creating a duplicate order.
6. IF the Shopify order creation fails after a verified payment, THE System SHALL enqueue a retry job and return a 202 Accepted response to the mobile app, ensuring the order is eventually created.

---

### Requirement 7

**User Story:** As a system operator, I want all payment and checkout data to be isolated per tenant, so that one tenant's data is never accessible to another.

#### Acceptance Criteria

1. WHEN any checkout session, payment transaction, or order mapping is created, THE System SHALL associate the record with the authenticated tenant's ID.
2. WHEN any API endpoint queries checkout sessions or payment transactions, THE System SHALL scope all database queries to the authenticated tenant's ID.
3. WHEN PSP credentials are stored in `tenant_payment_configs`, THE System SHALL encrypt the secret key fields at rest using application-level encryption.
4. WHEN a webhook is received, THE System SHALL resolve the tenant from the webhook payload or URL parameter before processing, and reject the webhook if the tenant cannot be resolved.

---

### Requirement 8

**User Story:** As a system operator, I want the checkout flow to fall back to Shopify's hosted checkout URL when native payment fails or is unavailable, so that users can always complete their purchase.

#### Acceptance Criteria

1. WHEN a PSP initialization fails with a non-retryable error, THE System SHALL include a `fallback_checkout_url` (Shopify hosted checkout URL) in the error response.
2. WHEN a tenant has no enabled native payment methods, THE System SHALL return a `fallback_checkout_url` in the `GET /api/v1/payment-settings` response.
3. WHEN the mobile app receives a `fallback_checkout_url`, THE System SHALL ensure the URL is a valid Shopify checkout URL scoped to the tenant's Shopify domain.

---

### Requirement 9

**User Story:** As a system operator, I want the checkout and payment APIs to be idempotent and safe to retry, so that network failures do not result in duplicate charges or duplicate orders.

#### Acceptance Criteria

1. WHEN a client sends an `Idempotency-Key` header with a POST request, THE System SHALL store the response for that key and return the cached response for any subsequent identical requests within a 24-hour window.
2. WHEN the Shopify order creation mutation is called, THE System SHALL use a deterministic idempotency key derived from the `checkout_session_id` to prevent duplicate orders.
3. WHEN a payment initialization request is retried with the same idempotency key, THE System SHALL return the original PSP session data without calling the PSP API again.

---

### Requirement 10

**User Story:** As a system operator, I want a well-defined state machine for checkout sessions, so that invalid state transitions are rejected and the system remains consistent.

#### Acceptance Criteria

1. THE System SHALL enforce the following valid state transitions for a checkout session: `pending` → `payment_initiated`, `payment_initiated` → `awaiting_confirmation`, `awaiting_confirmation` → `authorized`, `authorized` → `captured`, `captured` → `order_created`, any state → `failed`.
2. WHEN an operation attempts an invalid state transition, THE System SHALL return a 409 Conflict response and log the attempted transition.
3. WHEN a checkout session reaches the `failed` state, THE System SHALL record the failure reason and timestamp.
4. WHEN a checkout session has been in `payment_initiated` state for more than 30 minutes without progression, THE System SHALL transition it to `expired` via a scheduled job.
