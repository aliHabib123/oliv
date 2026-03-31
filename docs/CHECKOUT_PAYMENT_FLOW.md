# Checkout & Payment API Flow

## Overview

The system supports a **SDK-based checkout flow** where the mobile app handles payment processing via PSP SDKs (Tap, Tabby, Tamara, APS).

---

## Recommended Flow

```
1. POST /v1/checkout/session 
   → Returns session_id
   
2. Mobile SDK processes payment
   → Returns charge_id/fort_id from PSP
   
3. POST /v1/checkout/record-payment (NEW - to be implemented)
   → Records PaymentTransaction with psp_transaction_id
   
4. POST /v1/checkout/confirm
   → Verifies with PSP (double-check)
   → Creates Shopify order
   → Clears cart
   → Returns order details
```

---

## API Endpoints

### 1. Create Checkout Session

**Endpoint:** `POST /v1/checkout/session`

**Headers:**
- `X-App-Key` (required)
- `Idempotency-Key` (required, UUID)

**Request Body:**
```json
{
  "line_items": [
    {
      "variant_id": "gid://shopify/ProductVariant/123",
      "quantity": 1
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
  "session_id": "uuid-here",
  "amount": "150.00",
  "currency": "AED",
  "expires_at": "2026-03-31T15:00:00+00:00"
}
```

---

### 2. Record Payment (TO BE IMPLEMENTED)

**Endpoint:** `POST /v1/checkout/record-payment`

**Purpose:** Record the PSP transaction after mobile SDK completes payment.

**Request Body:**
```json
{
  "session_id": "uuid-here",
  "psp_type": "tap",
  "psp_transaction_id": "chg_xxxxxxxx"
}
```

**Response:**
```json
{
  "ok": true,
  "transaction_id": "uuid"
}
```

---

### 3. Confirm Checkout

**Endpoint:** `POST /v1/checkout/confirm`

**Request Body:**
```json
{
  "session_id": "uuid-here"
}
```

**What it does:**
1. Verifies payment with PSP (server-side double-check)
2. Creates Shopify order via Admin API
3. Marks order as paid
4. Clears the cart (TODO)
5. Returns order details

**Response:**
```json
{
  "ok": true,
  "shopify_order_id": "gid://shopify/Order/123",
  "shopify_order_name": "#1001",
  "status": "order_created"
}
```

---

## Session State Machine

```
pending
   ↓
payment_initiated
   ↓
awaiting_confirmation
   ↓
authorized
   ↓
captured
   ↓
order_created (terminal)

Any state → failed (terminal)
payment_initiated → expired (terminal)
```

---

## Supported PSPs

| PSP | Type | SDK Flow |
|-----|------|----------|
| **Tap** | Cards | ✅ Mobile SDK creates charge |
| **APS (PayFort)** | Cards | ✅ SDK with tokenization |
| **Tabby** | BNPL | WebView redirect |
| **Tamara** | BNPL | WebView redirect |

---

## TODO Items

- [ ] Implement `/v1/checkout/record-payment` endpoint
- [ ] Add Tap/APS verification to `verifyWithPsp()`
- [ ] Add cart clearing after order creation in `confirm()`
- [ ] Add Google Pay support

---

## Related Files

- `app/Http/Controllers/Api/CheckoutSessionController.php`
- `app/Services/Checkout/CheckoutSessionService.php`
- `app/Models/CheckoutSession.php`
- `app/Models/PaymentTransaction.php`

---

## Notes

- **Idempotency:** Both session creation and confirm are idempotent
- **Fallback:** If Shopify order creation fails, it's queued for retry via `CreateShopifyOrderJob`
- **Session expiry:** Sessions expire after 30 minutes
