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

Get all app settings including logo, colors, social media, policies.

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
    }
  }
}
```

---

### 2. Get Introduction Page

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

### 3. Get Home Layout

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

Create a checkout and get the webview URL.

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
      "variant_id": "gid://shopify/ProductVariant/456",
      "quantity": 2
    },
    {
      "variant_id": "gid://shopify/ProductVariant/789",
      "quantity": 1
    }
  ]
}
```

**Response:**
```json
{
  "ok": true,
  "checkout": {
    "id": "gid://shopify/Checkout/xyz123",
    "webUrl": "https://store.myshopify.com/checkout/xyz123",
    "totalPrice": {
      "amount": "89.97",
      "currencyCode": "USD"
    }
  }
}
```

**Usage:**
Open the `webUrl` in a webview. User can enter shipping, payment, and discount codes in Shopify's checkout page.

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
