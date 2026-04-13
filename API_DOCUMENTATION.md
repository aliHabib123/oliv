# 📱 Mobile API Documentation

Base URL: `https://yourdomain.com/api`

## 🔐 Authentication

All requests require these headers:

```
X-App-Key: {your-app-key}
```

Optional headers for authenticated users:
```
X-Shopify-Customer-Token: {shopify-customer-access-token}
X-Device-ID: {unique-device-id}
```

---

## 📄 Content & Configuration APIs

### 1. Get App Configuration

Get all app settings including logo, colors, social media, policies, e-commerce settings, and add-ons.

**Endpoint:** `GET /v1/app-config`

**Headers:**
```
X-App-Key: required
```

**Response:**
```json
{
  "ok": true,
  "config": {
    "basic": {
      "app_name": "My Store",
      "app_logo": "https://...",
      "app_description": "..."
    },
    "contact": {
      "phone": "+1234567890",
      "email": "support@store.com",
      "address": "123 Main St"
    },
    "social_media": {
      "facebook": "https://facebook.com/store",
      "instagram": "https://instagram.com/store",
      "twitter": "https://twitter.com/store"
    },
    "static_pages": {
      "privacy_policy_url": "https://...",
      "return_policy_url": "https://...",
      "shipping_policy_url": "https://...",
      "payment_policy_url": "https://...",
      "about_us_url": "https://..."
    },
    "ecommerce": {
      "shipping_promise": "Free shipping on orders over $50",
      "payment_options_image": "https://storage.../payment-methods.jpg",
      "tabby_enabled": true,
      "tabby_text": "Split in 4. No interest, no fees",
      "tamara_enabled": true,
      "tamara_text": "Pay in 3 easy installments"
    },
    "addons": {
      "whatsapp_chat_number": "+1234567890"
    }
  }
}
```

---

### 2. Get Payment Settings

Get Shopify payment settings including accepted card brands, currency, and supported digital wallets.

**Endpoint:** `GET /v1/payment-settings`

**Headers:**
```
X-App-Key: required
```

**Response:**
```json
{
  "ok": true,
  "payment_settings": {
    "acceptedCardBrands": ["VISA", "MASTERCARD", "AMEX"],
    "cardVaultUrl": "https://vault.shopify.com/...",
    "countryCode": "US",
    "currencyCode": "USD",
    "enabledPresentmentCurrencies": ["USD", "EUR"],
    "shopifyPaymentsAccountId": "gid://shopify/ShopifyPaymentsAccount/12345",
    "supportedDigitalWallets": ["APPLE_PAY", "GOOGLE_PAY"]
  }
}
```

---

### 3. Get Tenant Addons

Get list of active addons subscribed by the tenant with their keys for feature checking.

**Endpoint:** `GET /v1/addons`

**Headers:**
```
X-App-Key: required
```

**Response:**
```json
{
  "ok": true,
  "addons": [
    {
      "id": 1,
      "name": "WhatsApp Integration",
      "key": "whatsapp_integration",
      "description": "Enable WhatsApp chat support",
      "price": "29.99",
      "subscribed_at": "2024-01-15T10:30:00Z",
      "expires_at": "2025-01-15T10:30:00Z"
    },
    {
      "id": 2,
      "name": "Advanced Analytics",
      "key": "advanced_analytics",
      "description": "Get detailed insights and reports",
      "price": "49.99",
      "subscribed_at": "2024-01-20T14:00:00Z",
      "expires_at": null
    }
  ]
}
```

**Usage:**
Use the `key` field to check for specific features in your app:
```javascript
const hasWhatsApp = addons.some(addon => addon.key === 'whatsapp_integration');
if (hasWhatsApp) {
  // Show WhatsApp chat button
}
```

---

### 4. Get Introduction Page

Get introduction/onboarding page content with banner, title, and features.

**Endpoint:** `GET /v1/introduction`

**Headers:**
```
X-App-Key: required
```

**Response:**
```json
{
  "ok": true,
  "intro": {
    "banner_image": "https://storage.../banner.jpg",
    "welcome_title": "WELCOME",
    "welcome_text": "Welcome to our store...",
    "feature_sections": [
      {
        "section_title": "WHY CHOOSE US?",
        "items": [
          {
            "icon": "https://storage.../icon1.png",
            "title": "FREE DELIVERY"
          },
          {
            "icon": "https://storage.../icon2.png",
            "title": "EASY RETURNS"
          }
        ]
      }
    ]
  }
}
```

---

### 5. Get Home Layout

Get home page layout with banner, blocks, and featured content.

**Endpoint:** `GET /v1/home-layout`

**Headers:**
```
X-App-Key: required
```

**Response:**
```json
{
  "ok": true,
  "layout": {
    "banner_image": "https://storage.../home-banner.jpg",
    "banner_title": "Shop Now",
    "banner_subtitle": "Best Deals",
    "blocks": [
      {
        "type": "categories",
        "title": "Shop by Category",
        "category_ids": ["123", "456"]
      },
      {
        "type": "featured_products",
        "title": "Featured Products",
        "product_ids": ["789", "101112"]
      },
      {
        "type": "brands",
        "title": "Popular Brands",
        "brand_names": ["Brand A", "Brand B"]
      }
    ]
  }
}
```

---

## 🛒 Cart APIs

All cart endpoints return enhanced line item information including:
- **priceV2**: Current price of the variant
- **compareAtPriceV2**: Original/sale price (if available, null otherwise)
- **quantityAvailable**: Stock quantity available
- **selectedOptions**: Array of variant attributes (e.g., Size, Color)
- **image**: Variant-specific image (falls back to product image if not set)
- **product**: Parent product information including title, description, options, and featured image

**Identifying Simple vs Variable Products:**
- **Simple Product**: `options` array has one item with `name: "Title"` and `values: ["Default Title"]`
- **Variable Product**: `options` array has meaningful options (e.g., Size, Color) with real values
- Use `options` to determine whether to show variant selection UI in your app

### 4. Add to Cart

Add a product variant to the cart.

**Endpoint:** `POST /v1/cart/add`

**Headers:**
```
X-App-Key: required
X-Shopify-Customer-Token: optional (for logged-in users)
X-Device-ID: required (for guest tracking)
```

**Request Body:**
```json
{
  "product_id": "gid://shopify/Product/123",
  "variant_id": "gid://shopify/ProductVariant/456",
  "quantity": 2,
  "attributes": {
    "custom_field": "value"
  }
}
```

**Response:**
```json
{
  "ok": true,
  "cart": {
    "id": "gid://shopify/Cart/abc123",
    "lines": [
      {
        "id": "gid://shopify/CartLine/xyz",
        "quantity": 2,
        "merchandise": {
          "id": "gid://shopify/ProductVariant/456",
          "title": "Product Name - Size M",
          "priceV2": {
            "amount": "29.99",
            "currencyCode": "USD"
          },
          "compareAtPriceV2": {
            "amount": "39.99",
            "currencyCode": "USD"
          },
          "quantityAvailable": 100,
          "selectedOptions": [
            {
              "name": "Size",
              "value": "M"
            },
            {
              "name": "Color",
              "value": "Blue"
            }
          ],
          "image": {
            "url": "https://cdn.shopify.com/variant-image.jpg",
            "altText": "Product variant image"
          },
          "product": {
            "id": "gid://shopify/Product/123",
            "title": "Product Name",
            "description": "Detailed product description with features and specifications",
            "options": [
              {
                "name": "Size",
                "values": ["S", "M", "L", "XL"]
              },
              {
                "name": "Color",
                "values": ["Red", "Blue", "Green"]
              }
            ],
            "featuredImage": {
              "url": "https://cdn.shopify.com/product-image.jpg",
              "altText": "Product featured image"
            }
          }
        }
      }
    ],
    "estimatedCost": {
      "totalAmount": {
        "amount": "59.98",
        "currencyCode": "USD"
      }
    }
  }
}
```

**Error Response:**
```json
{
  "ok": false,
  "error": "Error message"
}
```

---

### 5. Remove from Cart

Remove a line item from the cart.

**Endpoint:** `POST /v1/cart/remove`

**Headers:**
```
X-App-Key: required
X-Shopify-Customer-Token: optional
X-Device-ID: required
```

**Request Body:**
```json
{
  "line_id": "gid://shopify/CartLine/xyz"
}
```

**Response:**
```json
{
  "ok": true,
  "cart": {
    "id": "gid://shopify/Cart/abc123",
    "lines": [],
    "estimatedCost": {
      "totalAmount": {
        "amount": "0.00",
        "currencyCode": "USD"
      }
    }
  }
}
```

---

### 6. Update Cart Line

Update quantity of a cart line item.

**Endpoint:** `POST /v1/cart/update`

**Headers:**
```
X-App-Key: required
X-Shopify-Customer-Token: optional
X-Device-ID: required
```

**Request Body:**
```json
{
  "line_id": "gid://shopify/CartLine/xyz",
  "quantity": 5
}
```

**Response:**
```json
{
  "ok": true,
  "cart": {
    "id": "gid://shopify/Cart/abc123",
    "lines": [
      {
        "id": "gid://shopify/CartLine/xyz",
        "quantity": 5,
        "merchandise": { "..." }
      }
    ],
    "estimatedCost": {
      "totalAmount": {
        "amount": "149.95",
        "currencyCode": "USD"
      }
    }
  }
}
```

---

### 7. Get Cart

Retrieve current cart state.

**Endpoint:** `GET /v1/cart`

**Headers:**
```
X-App-Key: required
X-Shopify-Customer-Token: optional
X-Device-ID: required
```

**Response:**
```json
{
  "ok": true,
  "cart": {
    "id": "gid://shopify/Cart/abc123",
    "lines": [
      {
        "id": "gid://shopify/CartLine/xyz",
        "quantity": 2,
        "merchandise": {
          "id": "gid://shopify/ProductVariant/456",
          "title": "Product Name - Size M",
          "priceV2": {
            "amount": "29.99",
            "currencyCode": "USD"
          },
          "compareAtPriceV2": {
            "amount": "39.99",
            "currencyCode": "USD"
          },
          "quantityAvailable": 100,
          "selectedOptions": [
            {
              "name": "Size",
              "value": "M"
            }
          ],
          "image": {
            "url": "https://cdn.shopify.com/variant-image.jpg",
            "altText": "Product variant image"
          },
          "product": {
            "id": "gid://shopify/Product/123",
            "title": "Product Name",
            "description": "Detailed product description with features and specifications",
            "options": [
              {
                "name": "Size",
                "values": ["S", "M", "L", "XL"]
              },
              {
                "name": "Color",
                "values": ["Red", "Blue", "Green"]
              }
            ],
            "featuredImage": {
              "url": "https://cdn.shopify.com/product-image.jpg",
              "altText": "Product featured image"
            }
          }
        }
      }
    ],
    "estimatedCost": {
      "totalAmount": {
        "amount": "59.98",
        "currencyCode": "USD"
      }
    }
  }
}
```

**If no cart exists:**
```json
{
  "ok": true,
  "cart": null
}
```

---

## 💳 Checkout API

### 8. Create Checkout

Create a checkout and get the checkout URL for completing the purchase.

**Important:** This endpoint uses Shopify's Cart API (Checkout API was deprecated in 2023-04+). A cart is created with your items and returns a `checkout_url` for the Shopify-hosted checkout page.

**Endpoint:** `POST /v1/checkout/create`

**Headers:**
```
X-App-Key: required
X-Shopify-Customer-Token: optional (for logged-in users)
X-Device-ID: required
```

**Request Body:**
```json
{
  "line_items": [
    {
      "variant_id": "gid://shopify/ProductVariant/42617526059051",
      "quantity": 2
    },
    {
      "variant_id": "gid://shopify/ProductVariant/42617526059052",
      "quantity": 1
    }
  ]
}
```

**Response:**
```json
{
  "ok": true,
  "checkout_id": "gid://shopify/Cart/hWN7U1cy08CUxTrBdR9TIXwK?key=abc123",
  "checkout_url": "https://your-store.myshopify.com/cart/c/hWN7U1cy08CUxTrBdR9TIXwK",
  "is_authenticated": false
}
```

**Response Fields:**
- `checkout_id`: Cart ID that can be used for tracking
- `checkout_url`: Direct URL to Shopify's hosted checkout page - open this in a webview or browser
- `is_authenticated`: Whether the user was logged in with a customer token

**Usage:**
1. Call this endpoint with the items you want to checkout
2. Open the `checkout_url` in a webview or browser
3. User completes shipping, payment, and can apply discount codes on Shopify's secure checkout page
4. After payment, user is redirected back or sees order confirmation

**Notes:**
- The checkout URL is valid for a limited time (typically 30 days)
- If user is authenticated (has X-Shopify-Customer-Token), their saved addresses and payment methods will be available
- This creates a new cart each time - it doesn't reuse existing cart data

---

## � Native Checkout API (Mobile SDK)

Native checkout flow for in-app payments using PSPs like APS (Amazon Payment Services) and Tap.

### Create Checkout Session

Create a checkout session for native mobile payment processing.

**Endpoint:** `POST /v1/checkout/session`

**Headers:**
```
X-App-Key: required
Idempotency-Key: required (UUID)
```

**Request Body (Option 1 - with cart_id, recommended):**
```json
{
  "cart_id": "gid://shopify/Cart/abc123?key=xyz",
  "customer": {
    "email": "customer@example.com",
    "phone": "+971501234567"
  },
  "shipping_address": {
    "first_name": "John",
    "last_name": "Doe",
    "address1": "123 Main Street",
    "city": "Dubai",
    "country_code": "AE",
    "zip": "12345"
  },
  "amount": 240.00,
  "currency": "AED"
}
```

**Request Body (Option 2 - with line_items):**
```json
{
  "line_items": [
    {
      "variant_id": "gid://shopify/ProductVariant/42617526059051",
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
    "address1": "123 Main Street",
    "city": "Dubai",
    "country_code": "AE",
    "zip": "12345"
  },
  "amount": 240.00,
  "currency": "AED",
  "cart_id": "gid://shopify/Cart/abc123?key=xyz"
}
```

**Parameters:**
- `cart_id` (optional): Shopify cart ID - **if provided without line_items, items are fetched from cart automatically. Cart is cleared after successful payment.**
- `line_items` (optional): Array of items to purchase - **required if cart_id not provided**
- `customer` (required): Customer email and phone
- `shipping_address` (required): Shipping address details
- `amount` (required): Total order amount
- `currency` (required): 3-letter currency code (e.g., AED, USD)

**Note:** Either `cart_id` or `line_items` must be provided. Using `cart_id` alone is recommended as it automatically fetches items and enables cart clearing.

**Response:**
```json
{
  "ok": true,
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "amount": "240.00",
  "currency": "AED",
  "expires_at": "2026-01-15T12:30:00+00:00"
}
```

---

### Record Payment Transaction

Record a payment transaction from the mobile SDK after processing payment.

**Endpoint:** `POST /v1/checkout/record-payment`

**Headers:**
```
X-App-Key: required
```

**Request Body:**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "psp_type": "aps",
  "psp_transaction_id": "169996200030488818"
}
```

**Parameters:**
- `session_id` (required): The checkout session UUID
- `psp_type` (required): Payment provider - `tap` or `aps`
- `psp_transaction_id` (required): Transaction ID from PSP (charge_id for Tap, fort_id for APS)

**Response:**
```json
{
  "ok": true,
  "transaction_id": 123,
  "status": "pending"
}
```

---

### Confirm Checkout

Confirm the checkout after payment is processed. This verifies the payment with the PSP and creates the Shopify order.

**Endpoint:** `POST /v1/checkout/confirm`

**Headers:**
```
X-App-Key: required
```

**Request Body:**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Success Response:**
```json
{
  "ok": true,
  "shopify_order_id": "gid://shopify/Order/5678901234567",
  "shopify_order_name": "#1001",
  "status": "order_created"
}
```

**Payment Not Authorized Response (402):**
```json
{
  "ok": false,
  "error": "Payment not authorized",
  "psp_status": "failed"
}
```

**Order Processing Response (202):**
```json
{
  "ok": true,
  "status": "processing",
  "message": "Order creation queued for retry"
}
```

---

### Native Checkout Flow

1. **Create Session** → `POST /v1/checkout/session` with cart_id
2. **Process Payment** → Use mobile SDK (APS/Tap) with session details
3. **Record Payment** → `POST /v1/checkout/record-payment` with PSP transaction ID
4. **Confirm Order** → `POST /v1/checkout/confirm` - verifies payment, creates order, clears cart

---

## 💳 Tap Payment Integration

Tap Payments integration for card payments, Apple Pay, and Google Pay.

### Generate Hash for Tap SDK

Generate an HMAC-SHA256 hash required for Tap SDK authentication. The hash is computed server-side using the secret key.

**Endpoint:** `POST /v1/payments/tap/hash`

**Headers:**
```
X-App-Key: required
Content-Type: application/json
```

**Request Body:**
```json
{
  "session_id": "a8636002-dfc4-4822-b287-46613b3d5c56",
  "amount": "118.00",
  "currency": "AED"
}
```

**Parameters:**
- `session_id` (required): The checkout session UUID
- `amount` (required): Payment amount as string (e.g., "118.00")
- `currency` (required): 3-letter currency code (e.g., AED, USD)

**Response:**
```json
{
  "ok": true,
  "hash": "a1b2c3d4e5f6..."
}
```

**Error Response:**
```json
{
  "ok": false,
  "error": "Tap payment not configured for this tenant"
}
```

---

### Initialize Tap Payment

Initialize a Tap payment session and get a payment URL for redirect-based checkout.

**Endpoint:** `POST /v1/payments/tap/initialize`

**Headers:**
```
X-App-Key: required
Content-Type: application/json
```

**Request Body:**
```json
{
  "session_id": "a8636002-dfc4-4822-b287-46613b3d5c56"
}
```

**Response:**
```json
{
  "ok": true,
  "charge_id": "chg_TS01A1234567890",
  "payment_url": "https://checkout.tap.company/...",
  "psp_type": "tap"
}
```

---

### Verify Tap Payment

Verify the status of a Tap payment.

**Endpoint:** `POST /v1/payments/tap/verify/{charge_id}`

**Headers:**
```
X-App-Key: required
```

**Response:**
```json
{
  "ok": true,
  "status": "authorized",
  "charge_id": "chg_TS01A1234567890"
}
```

**Status values:** `authorized`, `captured`, `failed`, `pending`

---

### Process Apple Pay with Tap

Process an Apple Pay payment through Tap.

**Endpoint:** `POST /v1/payments/tap/apple-pay`

**Headers:**
```
X-App-Key: required
Content-Type: application/json
```

**Request Body:**
```json
{
  "session_id": "a8636002-dfc4-4822-b287-46613b3d5c56",
  "apple_pay_token": {
    "paymentData": "...",
    "paymentMethod": { "..." },
    "transactionIdentifier": "..."
  }
}
```

**Response:**
```json
{
  "ok": true,
  "charge_id": "chg_TS01A1234567890",
  "status": "captured",
  "psp_type": "tap"
}
```

---

### Tap Payment Flow (Mobile SDK)

1. **Create Session** → `POST /v1/checkout/session`
2. **Get Public Key** → From `/v1/payment-settings` response
3. **Generate Hash** → `POST /v1/payments/tap/hash` with session_id, amount, currency
4. **Initialize Tap SDK** → Use public_key and hash from backend
5. **Process Payment** → Tap SDK handles card entry / Apple Pay / Google Pay
6. **Record Payment** → `POST /v1/checkout/record-payment` with `psp_type: "tap"` and `charge_id`
7. **Confirm Order** → `POST /v1/checkout/confirm`

---

## 🔧 Helper APIs

### 9. Get Default Variant

Get the default variant ID for a product (useful for products without explicit variants).

**Endpoint:** `GET /v1/products/{productId}/default-variant`

**Headers:**
```
X-App-Key: required
```

**Path Parameters:**
- `productId`: Numeric product ID (e.g., `7769452511275`) or GID format

**Response:**
```json
{
  "ok": true,
  "product_id": "gid://shopify/Product/7769452511275",
  "variant": {
    "id": "gid://shopify/ProductVariant/42617526059051",
    "title": "Default Title",
    "priceV2": {
      "amount": "29.99",
      "currencyCode": "USD"
    },
    "availableForSale": true
  },
  "all_variants": [
    {
      "id": "gid://shopify/ProductVariant/42617526059051",
      "title": "Default Title",
      "priceV2": { "..." },
      "availableForSale": true
    }
  ]
}
```

---

## 🔔 Push Notification APIs

### 10. Register Device Token

Register a device token for push notifications. Supports OneSignal and Firebase.

**Endpoint:** `POST /v1/device-tokens/register`

**Headers:**
```
X-App-Key: required
```

**Request Body:**
```json
{
  "push_token": "abc123def456...",
  "user_id": 1,
  "provider": "onesignal",
  "platform": "ios",
  "device_type": "iPhone 13 Pro",
  "app_version": "1.0.0",
  "metadata": {
    "device_name": "John's iPhone",
    "os_version": "17.2"
  }
}
```

**Parameters:**
- `push_token` (required): Device token from OneSignal or Firebase
- `user_id` (optional): User ID if logged in
- `provider` (optional): `onesignal` or `firebase` (defaults to `onesignal`)
- `platform` (optional): `ios` or `android`
- `device_type` (optional): Device model name
- `app_version` (optional): App version number
- `metadata` (optional): Additional device information

**Response:**
```json
{
  "success": true,
  "message": "Device token registered successfully",
  "data": {
    "id": 123,
    "push_token": "abc123def456...",
    "provider": "onesignal",
    "registered_at": "2026-01-24T12:23:45.000000Z"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "push_token": ["The push token field is required."]
  }
}
```

---

### 11. Unregister Device Token

Remove a device token when user logs out or uninstalls app.

**Endpoint:** `POST /v1/device-tokens/unregister`

**Headers:**
```
X-App-Key: required
```

**Request Body:**
```json
{
  "push_token": "abc123def456..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Device token unregistered successfully"
}
```

**Notes:**
- Tokens are automatically updated if the same token is registered again
- Token registration should happen on app launch and after user login
- Unregister token on logout or app uninstall
- Supports multiple devices per user

---

## 🔄 User Flow Examples

### Guest User Flow

1. **App Launch** → Call `/v1/app-config` and `/v1/introduction`
2. **Browse Products** → Use Shopify Storefront API
3. **Add to Cart** → `POST /v1/cart/add` with `X-Device-ID` only
4. **View Cart** → `GET /v1/cart`
5. **Checkout** → `POST /v1/checkout/create` → Open webview
6. **Complete Order** → Tracked via webhook

### Logged-in User Flow

1. **Login** → Get Shopify customer access token
2. **Add to Cart** → `POST /v1/cart/add` with `X-Shopify-Customer-Token` + `X-Device-ID`
3. **Checkout** → Token automatically associates customer with order
4. **Complete Order** → Customer email tracked in analytics

---

## 📊 Data Tracking

All cart and checkout operations are automatically logged for analytics:
- Which products are added to cart
- Checkout creation events
- Order completion (via webhooks)
- Customer identification (logged-in vs guest)
- Device tracking for guest users

---

## ⚠️ Important Notes

### Product vs Variant IDs

- **Always use variant IDs** for cart operations
- Every product has at least one variant (even "simple" products)
- Use `/v1/products/{id}/default-variant` to get variant ID if needed

### Cart State

- Carts are stored in both your database and Shopify
- Cart syncs with Shopify on every operation
- Cart automatically created on first add to cart

### Checkout Flow

- Discount codes applied in Shopify checkout webview
- Customer can update cart in checkout webview
- Webhooks track order completion

### Headers

- `X-App-Key`: Required for all requests (tenant identification)
- `X-Shopify-Customer-Token`: Optional, for authenticated users
- `X-Device-ID`: Required for cart/checkout (UUID format recommended)

---

## 🐛 Error Handling

All endpoints return consistent error format:

```json
{
  "ok": false,
  "error": "Error message description"
}
```

Common HTTP status codes:
- `200`: Success
- `400`: Bad request (validation error)
- `404`: Resource not found
- `500`: Server error

---

## 🧪 Testing

Use these test endpoints:
1. Get config: `GET /v1/app-config`
2. Get variant: `GET /v1/products/123/default-variant`
3. Add to cart: `POST /v1/cart/add`
4. Create checkout: `POST /v1/checkout/create`

Generate a random UUID for `X-Device-ID` during testing.

---

## 📞 Support

For questions or issues, contact your backend team.
