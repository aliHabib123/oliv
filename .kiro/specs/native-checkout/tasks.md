# Implementation Plan

## Already Implemented

The following are already in place and require no further work:
- `payment_gateways` table migration and `PaymentGateway` model with `getPublicConfig()` / `getPrivateFields()`
- `PaymentGatewayType` enum with per-gateway public/private field definitions
- `GET /api/v1/payment-settings` endpoint (caching, public-only fields, empty array fallback)
- `Tenant::paymentGateways()` relationship

---

- [x] 1. Install eris/eris property-based testing library
  - Run `composer require --dev giorgiosironi/eris`
  - Verify it resolves under PHP 8.2 / PHPUnit 11
  - _Requirements: Testing Strategy (design.md)_

- [x] 2. Database migrations for native checkout tables
- [x] 2.1 Create checkout_sessions migration
  - Add all fields: session_uuid, idempotency_key, status enum, psp_type, amount, currency, customer_email, customer_phone, shipping_address (JSON), line_items (JSON), shopify_cart_id, fallback_url, failure_reason, expires_at
  - Add indexes: tenant_status, session_uuid (unique), idempotency, expires_at
  - _Requirements: 2.1, 2.2, 10.1_

- [x] 2.2 Create payment_transactions migration
  - Add all fields: tenant_id, checkout_session_id, psp_type, psp_transaction_id, psp_session_data (JSON), status enum, amount, currency, idempotency_key, failure_reason, authorized_at, captured_at
  - Add indexes: tenant, session, psp_transaction, idempotency
  - _Requirements: 3.2, 4.2, 5.3_

- [x] 2.3 Create shopify_orders_mapping migration
  - Add all fields: tenant_id, checkout_session_id (unique), payment_transaction_id, shopify_order_id, shopify_order_name, shopify_order_data (JSON)
  - Add indexes: tenant, shopify_order
  - _Requirements: 6.4, 6.5_

- [x] 3. Eloquent models for new tables
- [x] 3.1 Create CheckoutSession model
  - Define fillable, casts (JSON fields, decimal amount, timestamps)
  - Add relationships: belongsTo Tenant, hasMany PaymentTransaction, hasOne ShopifyOrderMapping
  - Add helper methods: isInState(), canTransitionTo(), markAsFailed()
  - _Requirements: 2.1, 10.1_

- [x] 3.2 Create PaymentTransaction model
  - Define fillable, casts, relationships: belongsTo CheckoutSession, belongsTo Tenant
  - Add helper: isTerminal() returning true for captured/failed states
  - _Requirements: 3.2, 5.5_

- [x] 3.3 Create ShopifyOrderMapping model
  - Define fillable, casts, relationships: belongsTo CheckoutSession, belongsTo Tenant
  - _Requirements: 6.4_

- [x] 4. CheckoutSessionService — state machine and session lifecycle
- [x] 4.1 Implement state machine transition map and enforcement
  - Define VALID_TRANSITIONS constant map
  - Implement transition(CheckoutSession, string $toState) method that throws on invalid transition
  - _Requirements: 10.1, 10.2_

- [ ]* 4.2 Write property test for state machine enforcement (Property 4)
  - **Property 4: State machine only allows valid transitions**
  - Generate random (current_state, target_state) pairs, assert only valid pairs succeed
  - **Validates: Requirements 10.1, 10.2**

- [x] 4.3 Implement createSession() with idempotency
  - Accept validated payload + idempotency key + tenant
  - Check for existing session with same tenant + idempotency key, return it if found
  - Lock amount/currency from line items, store snapshot
  - Set expires_at to now + 30 minutes
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ]* 4.4 Write property test for idempotency deduplication (Property 3)
  - **Property 3: Idempotency key deduplication**
  - Call createSession N times with same key, assert exactly 1 DB record and identical responses
  - **Validates: Requirements 2.2, 2.3, 9.1**

- [ ]* 4.5 Write property test for amount immutability (Property 2)
  - **Property 2: Checkout session amount is immutable after creation**
  - Create session, attempt PSP init with different amount in body, assert stored amount is used
  - **Validates: Requirements 2.4, 3.5, 4.5**

- [x] 5. TabbyService — Tabby API integration
- [x] 5.1 Implement initializePayment(CheckoutSession, PaymentGateway) method
  - Build Tabby Create Session request from session's stored amount, currency, line_items, customer
  - Call Tabby API using tenant's server-side secret_key from PaymentGateway config
  - Return psp_transaction_id and web_url on success
  - Throw TabbyApiException on failure
  - _Requirements: 3.1, 3.4, 3.5_

- [x] 5.2 Implement verifyPayment(string $paymentId, PaymentGateway) method
  - Call Tabby Retrieve Payment API
  - Return normalized status: authorized | failed | pending
  - _Requirements: 6.1_

- [ ]* 5.3 Write property test for PSP init session transition (Property — PSP init transitions session)
  - **Property: Successful PSP initialization transitions session to payment_initiated**
  - Mock Tabby HTTP client, generate random valid sessions, assert status after init
  - **Validates: Requirements 3.2**

- [x] 6. TamaraService — Tamara API integration
- [x] 6.1 Implement initializePayment(CheckoutSession, PaymentGateway) method
  - Build Tamara Create Order request from session data
  - Call Tamara API using tenant's server-side api_token from PaymentGateway config
  - Return psp_transaction_id and checkout_url on success
  - Throw TamaraApiException on failure
  - _Requirements: 4.1, 4.4, 4.5_

- [x] 6.2 Implement verifyPayment(string $orderId, PaymentGateway) method
  - Call Tamara Get Order API
  - Return normalized status: authorized | failed | pending
  - _Requirements: 6.1_

- [x] 7. WebhookVerificationService
- [x] 7.1 Implement verifyTabbySignature(Request) method
  - Compute HMAC-SHA256 of raw payload using tenant's Tabby webhook secret
  - Compare with X-Tabby-Signature header using hash_equals
  - _Requirements: 5.1, 5.2_

- [x] 7.2 Implement verifyTamaraSignature(Request) method
  - Compute HMAC-SHA256 of raw payload using tenant's Tamara webhook secret
  - Compare with X-Tamara-Signature header using hash_equals
  - _Requirements: 5.1, 5.2_

- [ ]* 7.3 Write property test for webhook HMAC validation (Property — webhook signature)
  - **Property: Valid HMAC passes, any tampered payload or wrong key fails**
  - Generate random payloads and keys, assert valid signatures pass and tampered ones return 401
  - **Validates: Requirements 5.1, 5.2**

- [x] 8. ShopifyAdminService — order creation
- [x] 8.1 Implement createOrder(CheckoutSession) method
  - Build orderCreate GraphQL mutation variables from session line_items, shipping_address, customer
  - Use deterministic idempotency key derived from session_uuid
  - Return Shopify order GID and order name
  - _Requirements: 6.2, 9.2_

- [x] 8.2 Implement markOrderAsPaid(string $shopifyOrderId, PaymentTransaction) method
  - Call orderMarkAsPaid mutation with order ID
  - _Requirements: 6.2_

- [ ]* 8.3 Write property test for duplicate Shopify order prevention (Property 8)
  - **Property 8: Duplicate Shopify order prevention**
  - Call confirm N times on same confirmed session, assert exactly 1 shopify_orders_mapping row
  - **Validates: Requirements 6.5, 9.2**

- [ ]* 8.4 Write property test for no order without verified payment (Property 5)
  - **Property 5: Shopify order is never created without verified payment**
  - Generate sessions in non-authorized states, call confirm, assert no order mapping created
  - **Validates: Requirements 6.2, 6.3**

- [x] 9. Controllers
- [x] 9.1 Create CheckoutSessionController with create() and confirm() actions
  - create(): validate request (line_items, shipping_address, customer email+phone), resolve tenant, call CheckoutSessionService::createSession(), return 201
  - confirm(): validate session_id, call PSP verifyPayment(), call ShopifyAdminService, return 200/202/402/409
  - Add Swagger OA annotations for both endpoints
  - _Requirements: 2.1, 2.5, 6.1, 6.2, 6.3, 6.5_

- [x] 9.2 Create TabbyController with initialize() and webhook() actions
  - initialize(): validate session_id, call TabbyService::initializePayment(), update session state via CheckoutSessionService
  - webhook(): verify HMAC via WebhookVerificationService, parse payload, update PaymentTransaction and CheckoutSession status
  - Add Swagger OA annotations
  - _Requirements: 3.1, 3.2, 3.3, 5.1, 5.3, 5.4, 5.5_

- [x] 9.3 Create TamaraController with initialize() and webhook() actions
  - initialize(): validate session_id, call TamaraService::initializePayment(), update session state via CheckoutSessionService
  - webhook(): verify HMAC via WebhookVerificationService, parse payload, update PaymentTransaction and CheckoutSession status
  - Add Swagger OA annotations
  - _Requirements: 4.1, 4.2, 4.3, 5.1, 5.3, 5.4, 5.5_

- [ ]* 9.4 Write property test for tenant data isolation (Property 7)
  - **Property 7: Tenant data isolation**
  - Create sessions for two tenants, assert tenant A requests never return tenant B data
  - **Validates: Requirements 7.1, 7.2**

- [ ]* 9.5 Write property test for payment settings public field safety (Property 1)
  - **Property 1: Payment settings expose only public fields**
  - Generate PaymentGateway records with arbitrary configs, assert no private fields in API response
  - **Validates: Requirements 1.3**

- [ ]* 9.6 Write property test for webhook idempotency on terminal state (Property 6)
  - **Property 6: Webhook idempotency — terminal state is not reprocessed**
  - Generate webhook payloads for transactions already in terminal states, assert status unchanged and HTTP 200 returned
  - **Validates: Requirements 5.5**

- [x] 10. Register routes and middleware
  - Add POST v1/checkout/session to api.php under ResolveTenantFromAppKey middleware
  - Add POST v1/payments/tabby/initialize and v1/payments/tamara/initialize under ResolveTenantFromAppKey
  - Add POST v1/checkout/confirm under ResolveTenantFromAppKey
  - Add POST webhooks/tabby and webhooks/tamara outside tenant middleware with raw body preserved
  - _Requirements: 2.1, 3.1, 4.1, 5.1, 6.1_

- [x] 11. CreateShopifyOrderJob — async retry for failed order creation
  - Implement queued job that retries ShopifyAdminService::createOrder() up to 5 times with exponential backoff
  - On final failure, update checkout_session status to failed with reason
  - _Requirements: 6.6_

- [x] 12. ExpireCheckoutSessionsJob — scheduled cleanup
  - Query checkout_sessions where status = payment_initiated AND expires_at < now()
  - Transition each to expired state via CheckoutSessionService
  - Register in Console/Kernel or bootstrap/app.php schedule
  - _Requirements: 10.4_

- [x] 13. Checkpoint — ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Regenerate Swagger documentation
  - Run php artisan l5-swagger:generate
  - Verify all new endpoints appear in /api/documentation with correct request/response schemas
  - _Requirements: 3.1, 4.1, 6.1_
