# Postman Testing Guide - Checkout & Payment APIs

## Implementation Status

| Endpoint | Status | Notes |
|----------|--------|-------|
| `POST /v1/checkout/session` | ✅ Implemented | Creates checkout session |
| `POST /v1/checkout/confirm` | ✅ Implemented | Confirms & creates Shopify order |
| `POST /v1/checkout/record-payment` | ✅ Implemented | Records SDK payment |
| `POST /v1/payments/tap/initialize` | ✅ Implemented | Server-initiated Tap payment |
| `POST /v1/payments/aps/initialize` | ✅ Implemented | Server-initiated APS payment |
| `POST /v1/payments/tabby/initialize` | ✅ Implemented | BNPL - Tabby |
| `POST /v1/payments/tamara/initialize` | ✅ Implemented | BNPL - Tamara |

---

## Environment Setup

Create a Postman environment with these variables:

| Variable | Example Value |
|----------|---------------|
| `base_url` | `https://portal.oliv.tech/api` or `http://localhost:8000/api` |
| `app_key` | Your tenant's X-App-Key |

---

## Testing Flow

### Step 1: Test Connection

**Request:**
```
GET {{base_url}}/v1/ping
```

**Headers:**
```
X-App-Key: {{app_key}}
```

**Expected Response:**
```json
{
  "ok": true,
  "message": "pong"
}
```

---

### Step 2: Get Payment Settings

**Request:**
```
GET {{base_url}}/v1/payment-settings
```

**Headers:**
```
X-App-Key: {{app_key}}
```

**Expected Response:**
```json
{
  "ok": true,
  "gateways": [...],
  "card_brands": [...],
  "apple_pay": {...},
  "google_pay": {...}
}
```

---

### Step 3: Create Checkout Session

**Request:**
```
POST {{base_url}}/v1/checkout/session
```

**Headers:**
```
X-App-Key: {{app_key}}
Content-Type: application/json
Idempotency-Key: {{$guid}}
```

**Body:**
```json
{
  "line_items": [
    {
      "variant_id": "gid://shopify/ProductVariant/12345678901234",
      "quantity": 1
    }
  ],
  "customer": {
    "email": "test@example.com",
    "phone": "+971501234567"
  },
  "shipping_address": {
    "first_name": "Test",
    "last_name": "User",
    "address1": "123 Test Street",
    "city": "Dubai",
    "country_code": "AE",
    "zip": "12345"
  },
  "amount": "100.00",
  "currency": "AED"
}
```

**Expected Response:**
```json
{
  "ok": true,
  "session_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "amount": "100.00",
  "currency": "AED",
  "expires_at": "2026-04-01T14:30:00+00:00"
}
```

**Save to variable:** `session_id`

---

### Step 4: Record Payment (SDK Flow - Tap/APS)

Use this after the mobile SDK processes the payment and returns a charge_id.

**Request:**
```
POST {{base_url}}/v1/checkout/record-payment
```

**Headers:**
```
X-App-Key: {{app_key}}
Content-Type: application/json
```

**Body (Tap):**
```json
{
  "session_id": "{{session_id}}",
  "psp_type": "tap",
  "psp_transaction_id": "chg_xxxxxxxx"
}
```

**Body (APS):**
```json
{
  "session_id": "{{session_id}}",
  "psp_type": "aps",
  "psp_transaction_id": "fort_xxxxxxxx"
}
```

**Expected Response:**
```json
{
  "ok": true,
  "transaction_id": 123,
  "status": "pending"
}
```

---

### Step 5a: Initialize Payment (BNPL - Tabby)

**Request:**
```
POST {{base_url}}/v1/payments/tabby/initialize
```

**Headers:**
```
X-App-Key: {{app_key}}
Content-Type: application/json
```

**Body:**
```json
{
  "session_id": "{{session_id}}"
}
```

**Expected Response:**
```json
{
  "ok": true,
  "order_id": "tabby-order-id",
  "checkout_url": "https://checkout.tabby.ai/...",
  "psp_type": "tabby"
}
```

---

### Step 4b: Initialize Payment (BNPL - Tamara)

**Request:**
```
POST {{base_url}}/v1/payments/tamara/initialize
```

**Headers:**
```
X-App-Key: {{app_key}}
Content-Type: application/json
```

**Body:**
```json
{
  "session_id": "{{session_id}}"
}
```

**Expected Response:**
```json
{
  "ok": true,
  "order_id": "tamara-order-id",
  "checkout_url": "https://checkout.tamara.co/...",
  "psp_type": "tamara"
}
```

---

### Step 4c: Initialize Payment (Tap - Server Flow)

**Request:**
```
POST {{base_url}}/v1/payments/tap/initialize
```

**Headers:**
```
X-App-Key: {{app_key}}
Content-Type: application/json
```

**Body:**
```json
{
  "session_id": "{{session_id}}"
}
```

**Expected Response:**
```json
{
  "ok": true,
  "charge_id": "chg_xxxxxxxx",
  "payment_url": "https://checkout.payments.tap.company/...",
  "psp_type": "tap"
}
```

---

### Step 5: Confirm Checkout (After Payment)

**Request:**
```
POST {{base_url}}/v1/checkout/confirm
```

**Headers:**
```
X-App-Key: {{app_key}}
Content-Type: application/json
```

**Body:**
```json
{
  "session_id": "{{session_id}}"
}
```

**Expected Response (Success):**
```json
{
  "ok": true,
  "shopify_order_id": "gid://shopify/Order/123456789",
  "shopify_order_name": "#1001",
  "status": "order_created"
}
```

**Expected Response (Payment Not Ready):**
```json
{
  "ok": false,
  "error": "Payment not authorized",
  "psp_status": "pending"
}
```

---

## SDK Flow (Mobile App)

For mobile apps using PSP SDKs directly:

```
1. POST /v1/checkout/session → Get session_id
2. Use Tap/APS SDK in app → Get charge_id from SDK
3. POST /v1/checkout/record-payment (NOT YET IMPLEMENTED)
   → Record the payment transaction
4. POST /v1/checkout/confirm → Create Shopify order
```

**Note:** Step 3 needs to be implemented. Currently, the mobile SDK flow requires using the `initialize` endpoints which create server-side charges.

---

## Testing Tips

### Generate UUID for Idempotency-Key
In Postman, use `{{$guid}}` to auto-generate a UUID.

### Check Session Status
You can call `/v1/checkout/confirm` multiple times - it's idempotent. If already confirmed, it returns the existing order.

### Webhook Testing
For BNPL (Tabby/Tamara), payment confirmation happens via webhooks:
- `POST /webhooks/tabby`
- `POST /webhooks/tamara`

These update the session status to `authorized`, allowing `confirm` to proceed.

---

## Error Codes

| HTTP Code | Meaning |
|-----------|---------|
| 200 | Success |
| 201 | Created (session) |
| 202 | Accepted (order queued for retry) |
| 400 | Bad request / validation error |
| 402 | Payment not authorized |
| 404 | Session/tenant not found |
| 409 | Conflict (duplicate idempotency key or wrong state) |
| 502 | PSP API error |

---

## Common Issues

### "Session not in pending state"
The session already has a payment initiated. Create a new session.

### "Payment not authorized"
The PSP hasn't confirmed the payment yet. Wait for webhook or verify manually.

### "Tenant not found"
Invalid `X-App-Key` header.

### "Idempotency-Key header is required"
Add the `Idempotency-Key` header with a UUID value.
