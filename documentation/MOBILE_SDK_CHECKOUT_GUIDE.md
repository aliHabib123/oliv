# Mobile SDK Checkout Integration Guide

This guide explains how to integrate the native SDK checkout flow for mobile applications.

---

## Checkout Flow Options

| Endpoint | Purpose | Payment Handling |
|----------|---------|------------------|
| `/v1/checkout/create` | **Shopify Native Checkout** | Redirects to Shopify's checkout page (`webUrl`) |
| `/v1/checkout/session` | **Native SDK Checkout** | Mobile SDK handles payment (Tap, APS, Tabby, Tamara) |

### When to use which:

**`/v1/checkout/create` (Legacy/WebView)**
- User is redirected to Shopify's web checkout
- Payment handled by Shopify
- Returns `checkout_url` → open in WebView
- Less control, but simpler

**`/v1/checkout/session` (Native SDK Flow)**
- Payment handled inside the app via PSP SDK
- Full control over UX
- Better for native mobile experience
- Supports: Tap SDK, APS SDK, Apple Pay, Tabby, Tamara

---

## Native SDK Checkout Flow

```
┌─────────────────┐
│  1. Create      │
│  Checkout       │
│  Session        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  2. SDK         │
│  Processes      │
│  Payment        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  3. Record      │
│  Payment        │
│  Transaction    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  4. Confirm     │
│  & Create       │
│  Shopify Order  │
└─────────────────┘
```

---

## Step 1: Create Checkout Session

**Endpoint:** `POST /v1/checkout/session`

**Headers:**
```
X-App-Key: your_app_key
Content-Type: application/json
Idempotency-Key: <client-generated-uuid>
```

**Request Body:**
```json
{
  "line_items": [
    {
      "variant_id": "gid://shopify/ProductVariant/123456",
      "quantity": 2
    }
  ],
  "customer": {
    "email": "customer@example.com",
    "phone": "+971501234567"
  },
  "shipping_address": {
    "first_name": "John",
    "last_name": "Doe",
    "address1": "123 Main St",
    "city": "Dubai",
    "country_code": "AE",
    "zip": "12345"
  },
  "amount": "150.00",
  "currency": "AED"
}
```

**Response:**
```json
{
  "ok": true,
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "amount": "150.00",
  "currency": "AED",
  "expires_at": "2026-04-01T15:00:00+00:00"
}
```

---

## Step 2: Process Payment with SDK

After receiving the `session_id`, use the PSP's mobile SDK to process the payment.

### Tap Payments SDK
```swift
// iOS Example
let charge = TapCharge()
charge.amount = 150.00
charge.currency = "AED"
// ... configure charge
// On success, you'll receive a charge_id
```

### APS (PayFort) SDK

**Step 2a: Get SDK Token (required for APS)**

Before initializing the APS SDK, you must request an SDK token from the server.

**Endpoint:** `POST /v1/payments/aps/sdk-token`

**Request:**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "device_id": "unique-device-identifier"
}
```

**Response:**
```json
{
  "ok": true,
  "sdk_token": "token-from-aps",
  "merchant_identifier": "merchant_id",
  "access_code": "access_code"
}
```

**Step 2b: Initialize APS SDK with token**

```swift
// iOS Example
let fortRequest = FORTRequest()
fortRequest.sdkToken = sdkTokenResponse.sdkToken
fortRequest.merchantIdentifier = sdkTokenResponse.merchantIdentifier
fortRequest.accessCode = sdkTokenResponse.accessCode
fortRequest.amount = "15000" // in fils
fortRequest.currency = "AED"
// ... configure request
// On success, you'll receive a fort_id
```

```kotlin
// Android Example
val fortRequest = FortRequest()
fortRequest.sdkToken = sdkTokenResponse.sdkToken
fortRequest.merchantIdentifier = sdkTokenResponse.merchantIdentifier
fortRequest.accessCode = sdkTokenResponse.accessCode
fortRequest.amount = "15000"
fortRequest.currency = "AED"
// On success, you'll receive a fort_id
```

**Important:** Save the transaction ID returned by the SDK:
- **Tap:** `charge_id` (e.g., `chg_xxxxxxxx`)
- **APS:** `fort_id` (e.g., `FT123456789`)

---

## Step 3: Record Payment Transaction

**Endpoint:** `POST /v1/checkout/record-payment`

**Headers:**
```
X-App-Key: your_app_key
Content-Type: application/json
```

**Request Body (Tap):**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "psp_type": "tap",
  "psp_transaction_id": "chg_xxxxxxxx"
}
```

**Request Body (APS):**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "psp_type": "aps",
  "psp_transaction_id": "FT123456789"
}
```

**Response:**
```json
{
  "ok": true,
  "transaction_id": 123,
  "status": "pending"
}
```

---

## Step 4: Confirm Checkout

**Endpoint:** `POST /v1/checkout/confirm`

**Headers:**
```
X-App-Key: your_app_key
Content-Type: application/json
```

**Request Body:**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response:**
```json
{
  "ok": true,
  "shopify_order_id": "gid://shopify/Order/123456",
  "shopify_order_name": "#1001",
  "status": "order_created"
}
```

---

## Idempotency Key

### What is it?
A unique identifier (UUID) sent by the client to ensure the same request isn't processed twice. If the same idempotency key is sent again, the server returns the original response instead of creating a duplicate.

### Why it matters
Prevents duplicate charges/orders if a network error causes the client to retry.

### Who generates it?
**The client (mobile app) generates it** before making the API call.

### Implementation

**Swift (iOS):**
```swift
let idempotencyKey = UUID().uuidString
request.addValue(idempotencyKey, forHTTPHeaderField: "Idempotency-Key")
```

**Kotlin (Android):**
```kotlin
val idempotencyKey = UUID.randomUUID().toString()
request.addHeader("Idempotency-Key", idempotencyKey)
```

### Flow Diagram

```
[Mobile App]                           [Server]
     |                                    |
     |-- Generate UUID locally            |
     |                                    |
     |-- POST /v1/checkout/session ------>|
     |   Header: Idempotency-Key: abc123  |
     |                                    |-- Store idempotency_key: abc123
     |<-- 201 { session_id: xyz }         |
     |                                    |
     |-- [Network fails, retry] --------->|
     |   Header: Idempotency-Key: abc123  |
     |                                    |-- Find existing session with abc123
     |<-- 200 { session_id: xyz }         |-- Return existing (no duplicate)
```

### Best Practice
- Generate a **new UUID** for each new checkout attempt
- **Reuse the same UUID** only when retrying a failed request
- Store the UUID locally until the checkout is complete

---

## Error Handling

| HTTP Code | Meaning | Action |
|-----------|---------|--------|
| 200/201 | Success | Continue to next step |
| 400 | Bad request | Check request body format |
| 404 | Session not found | Create a new session |
| 409 | Session state conflict | Check session status |
| 422 | Validation error | Fix request parameters |
| 402 | Payment not authorized | Payment failed, show error |

---

## Complete Flow Example

```swift
// iOS Swift Example

// 1. Create session
let idempotencyKey = UUID().uuidString
let sessionResponse = await api.createCheckoutSession(
    lineItems: cart.items,
    customer: customerInfo,
    shippingAddress: address,
    amount: cart.total,
    currency: "AED",
    idempotencyKey: idempotencyKey
)

// 2. Process payment with Tap SDK
let chargeId = await TapSDK.processPayment(amount: cart.total)

// 3. Record the payment
let recordResponse = await api.recordPayment(
    sessionId: sessionResponse.sessionId,
    pspType: "tap",
    pspTransactionId: chargeId
)

// 4. Confirm and create order
let confirmResponse = await api.confirmCheckout(
    sessionId: sessionResponse.sessionId
)

// 5. Show success
showOrderConfirmation(orderName: confirmResponse.shopifyOrderName)
```

---

## Support

For questions or issues, contact the backend team.
