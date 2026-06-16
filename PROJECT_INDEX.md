# Oliv Project Index

> Multi-tenant SaaS platform for mobile e-commerce apps with Shopify integration.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 (PHP 8.2+) |
| Admin Panel | Filament 4.4 |
| Database | SQLite (dev) / MySQL (prod) via Eloquent |
| Frontend Build | Vite 7 + Tailwind CSS 4 |
| Payments | Stripe, APS (PayFort), Tap, Tabby, Tamara |
| Push Notifications | OneSignal |
| E-commerce | Shopify Storefront & Admin APIs |
| Permissions | Spatie Laravel Permission |
| API Docs | L5-Swagger (OpenAPI) |
| Testing | PHPUnit 11 |
| CI/CD | GitHub Actions → SSH deploy on `release` branch |

---

## Directory Structure

```
├── app/
│   ├── Console/Commands/       # Artisan commands
│   ├── Enums/                  # PaymentGatewayType
│   ├── Exceptions/             # Custom exceptions
│   ├── Filament/               # Admin panel (Resources, Pages, Widgets)
│   ├── Http/
│   │   ├── Controllers/Api/    # 15 API controllers
│   │   ├── Controllers/        # Web controllers (Subscription, TenantAddon)
│   │   └── Middleware/         # ResolveTenantFromAppKey
│   ├── Jobs/                   # CreateShopifyOrderJob
│   ├── Models/                 # 21 Eloquent models
│   ├── Providers/              # Service providers
│   └── Services/               # Domain services (Cart, Checkout, Payment, Shopify, OneSignal)
├── config/                     # Laravel + l5-swagger + permission configs
├── database/
│   ├── migrations/             # 43 migrations
│   └── seeders/                # Countries, Pricing, Database seeders
├── documentation/              # Technical guides (Apple Pay, Checkout Flow, Mobile SDK, Postman)
├── public/                     # Web root (CSS, fonts, images, JS)
├── resources/                  # Blade views, CSS, JS source
├── routes/                     # api.php, web.php, console.php
├── storage/                    # Logs, Swagger output, framework cache
└── tests/                      # Feature (PaymentApiTest) & Unit tests
```

---

## Models (21)

### Core / Tenant

| Model | Table | Purpose |
|-------|-------|---------|
| `Tenant` | tenants | Client/store — owns users, layouts, addons, config |
| `User` | users | System users with roles, belongs to tenant |
| `Package` | packages | Subscription plans |
| `Subscription` | subscriptions | Tenant → Package binding with billing |
| `Addon` | addons | Feature add-ons available for purchase |
| `TenantAddon` | tenant_addons | Tenant → Addon subscriptions |

### E-commerce / Checkout

| Model | Table | Purpose |
|-------|-------|---------|
| `Cart` | carts | Shopping cart per device/customer |
| `CartEvent` | cart_events | Cart activity log (add, remove, update) |
| `CheckoutEvent` | checkout_events | Checkout activity tracking |
| `CheckoutSession` | checkout_sessions | Native checkout session (amount, currency, expiry) |
| `PaymentTransaction` | payment_transactions | PSP transaction records |
| `ShopifyOrderMapping` | shopify_orders_mapping | Links sessions → Shopify orders |

### Content / Configuration

| Model | Table | Purpose |
|-------|-------|---------|
| `HomeLayout` | home_layouts | Home page layout configuration |
| `AppConfiguration` | app_configurations | App settings, branding, social links |
| `Template` | templates | UI templates |
| `DeviceToken` | device_tokens | Push notification device tokens |

### Payments / Pricing

| Model | Table | Purpose |
|-------|-------|---------|
| `PaymentGateway` | payment_gateways | PSP credentials per tenant |
| `CardBrand` | card_brands | Accepted card brands |
| `Country` | countries | Supported countries with currency |
| `PackagePricing` | package_pricing | Per-country package pricing + Stripe IDs |
| `AddonPricing` | addon_pricing | Per-country addon pricing + Stripe IDs |

---

## Services

| Service | Path | Responsibility |
|---------|------|----------------|
| `CartService` | `Services/Cart/` | Cart CRUD via Shopify Storefront API |
| `CheckoutSessionService` | `Services/Checkout/` | Create sessions, validate, expire |
| `AmazonPaymentService` | `Services/Payment/` | APS (PayFort) SDK token, charge, verify |
| `TapService` | `Services/Payment/` | Tap hash, charge, verify, Apple Pay |
| `TabbyService` | `Services/Payment/` | Tabby BNPL initialization |
| `TamaraService` | `Services/Payment/` | Tamara BNPL initialization |
| `WebhookVerificationService` | `Services/Payment/` | PSP webhook signature validation |
| `ShopifyStorefrontService` | `Services/Shopify/` | Storefront API (products, carts, checkout) |
| `ShopifyAdminService` | `Services/Shopify/` | Admin API (create orders, fulfillments) |
| `ShopifyAdminTokenService` | `Services/Shopify/` | OAuth token management |
| `OneSignalService` | `Services/OneSignal/` | Push notification delivery |
| `CategorySyncService` | `Services/` | Sync categories from Shopify |
| `CountryPricingService` | `Services/` | Resolve pricing by country |

---

## Controllers

### API Controllers (`app/Http/Controllers/Api/`)

| Controller | Key Endpoints |
|------------|--------------|
| `ContentController` | `GET /v1/ping`, `/v1/home-layout`, `/v1/introduction`, `/v1/app-config`, `/v1/payment-settings` |
| `ProductController` | `GET /v1/products/{id}/default-variant` |
| `CartController` | `POST /v1/cart/add`, `/remove`, `/update`; `GET /v1/cart` |
| `CheckoutController` | `POST /v1/checkout/create` (Shopify webview checkout) |
| `CheckoutSessionController` | `POST /v1/checkout/session`, `/record-payment`, `/confirm` |
| `PaymentController` | `POST /v1/pay` |
| `AmazonPaymentController` | `POST /v1/payments/aps/initialize`, `/sdk-token`, `/charge`, `/callback`, `/verify`, `/apple-pay` |
| `TapController` | `POST /v1/payments/tap/hash`, `/initialize`, `/callback`, `/verify`, `/refund`, `/apple-pay`; `GET /status` |
| `TabbyController` | `POST /v1/payments/tabby/initialize`; `POST /webhooks/tabby` |
| `TamaraController` | `POST /v1/payments/tamara/initialize`; `POST /webhooks/tamara` |
| `OrderController` | `GET /v1/orders/{id}/status`; `POST /v1/orders/{id}/tracking-status` |
| `DeviceTokenController` | `POST /v1/device-tokens/register`, `/unregister` |
| `AddonController` | `GET /v1/addons` |
| `ShopifyWebhookController` | `POST /webhooks/shopify/checkouts/create`, `/update`, `orders/create` |
| `OpenApiController` | Swagger spec endpoint |

### Web Controllers

| Controller | Routes |
|------------|--------|
| `SubscriptionController` | `/subscriptions`, `/subscriptions/subscribe`, `/subscriptions/payment`, Stripe webhook |
| `TenantAddonController` | `/tenant-addons`, subscribe/unsubscribe addon |

---

## Filament Admin Panel

### Resources

| Resource | Manages |
|----------|---------|
| `TenantResource` | Tenants (CRUD + relationships) |
| `UserResource` | Users with role assignment |
| `PackageResource` | Subscription packages |
| `PackagePricingResource` | Per-country package pricing |
| `AddonResource` | Available add-ons |
| `AddonPricingResource` | Per-country addon pricing |
| `PaymentGatewayResource` | PSP credentials per tenant |
| `CardBrandResource` | Accepted card brands |
| `TemplateResource` | UI templates |

### Pages

| Page | Purpose |
|------|---------|
| `AppSettings` | Global application settings |
| `ApsLogs` | Amazon Payment Services log viewer |
| `CartAnalytics` | Cart analytics dashboard |
| `HomeLayout` | Home page layout editor |
| `HomeLayoutAssignments` | Assign layouts to tenants |
| `Introduction` | Onboarding page editor |
| `ManageAddons` | Addon management |
| `Notifications` | Notification center |
| `SendNotification` | Push notification composer |
| `TemplateBuilder` | Visual template builder |

### Widgets

| Widget | Purpose |
|--------|---------|
| `CartEventsChart` | Cart activity over time |
| `CartStatsOverview` | Cart statistics summary |
| `PopularProductsChart` | Most added-to-cart products |
| `RecentCheckoutsTable` | Latest checkout events |

---

## Database Migrations (43)

### Infrastructure
- `create_users_table`, `create_cache_table`, `create_jobs_table`
- `create_permission_tables` (Spatie)

### Multi-Tenancy
- `create_tenants_table`, `add_tenant_id_to_users_table`
- `add_home_layout_to_tenants_table`, `add_intro_config_to_tenants_table`
- `add_email_phone_to_tenants_table`, `add_shopify_admin_token_to_tenants_table`
- `add_shopify_oauth_fields_to_tenants_table`

### Content
- `create_app_configurations_table`, `create_home_layouts_table`
- `migrate_existing_tenant_layouts_to_home_layouts_table`
- `add_indexes_to_home_layouts_table`, `create_templates_table`

### E-commerce
- `create_cart_events_table`, `create_carts_table`, `create_checkout_events_table`
- `create_checkout_sessions_table`, `create_payment_transactions_table`
- `create_shopify_orders_mapping_table`, `widen_idempotency_key_columns`

### Subscriptions & Billing
- `create_packages_table`, `create_addons_table`, `create_subscriptions_table`
- `create_tenant_addons_table`, `add_key_to_addons_table`
- `update_subscriptions_and_tenants_for_payment_flow`
- `add_past_due_status_to_subscriptions`
- `add_billing_period_and_addons_to_subscriptions_table`
- `add_stripe_subscription_item_id_to_tenant_addons_table`
- `add_subtitle_image_video_to_addons_table`

### Country Pricing
- `create_countries_table`, `create_package_pricing_table`
- `create_addon_pricing_table`, `add_country_code_to_subscriptions_table`

### Payments
- `create_payment_gateways_table`, `create_card_brands_table`
- `add_digital_wallet_support_to_payment_gateways`, `change_wallet_config_to_text`

### Device Management
- `create_device_tokens_table`

---

## API Authentication

All API requests require:
- `X-App-Key` header → resolved to a tenant via `ResolveTenantFromAppKey` middleware

Optional headers:
- `X-Shopify-Customer-Token` → authenticated Shopify customer
- `X-Device-ID` → device identification for cart/notifications
- `Idempotency-Key` → required for checkout session creation

---

## Key Architectural Patterns

1. **Multi-tenancy via header** — single database, tenant resolved from `X-App-Key` middleware (not subdomain/schema isolation)
2. **Service layer** — domain logic separated into service classes; controllers stay thin
3. **Multiple PSP support** — unified checkout session flow across APS, Tap, Tabby, Tamara
4. **Native mobile checkout** — Session → SDK payment → Record transaction → Confirm → Shopify order
5. **Shopify as catalog** — products/orders live in Shopify; app manages cart state and checkout orchestration
6. **Country-based pricing** — packages and addons priced per country with dedicated Stripe price IDs
7. **Queue-based order creation** — `CreateShopifyOrderJob` for async reliability
8. **Idempotency** — checkout sessions use idempotency keys to prevent duplicates
9. **Webhook-driven** — Shopify and PSP webhooks drive order/payment state changes

---

## Development

```bash
# Setup
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build

# Run (server + queue + logs + vite)
composer run dev

# Test
php artisan test

# Generate API docs
php artisan l5-swagger:generate
```

---

## Deployment

- Push to `release` branch triggers GitHub Actions
- SSH into server → `git reset --hard origin/release`
- `composer install --no-dev --optimize-autoloader`
- `php artisan migrate --force`
- Cache config, routes, views
- Generate Swagger docs

---

## Documentation

| File | Content |
|------|---------|
| `API_DOCUMENTATION.md` | Full mobile API reference with request/response examples |
| `COUNTRY_PRICING_IMPLEMENTATION.md` | Multi-country pricing implementation log |
| `SWAGGER_SETUP.md` | L5-Swagger configuration guide |
| `documentation/APPLE_PAY_SETUP.md` | Apple Pay configuration (APS + Tap) |
| `documentation/CHECKOUT_PAYMENT_FLOW.md` | End-to-end checkout flow diagram |
| `documentation/MOBILE_SDK_CHECKOUT_GUIDE.md` | Native SDK integration guide |
| `documentation/POSTMAN_TESTING_GUIDE.md` | Postman collection testing instructions |
| `documentation/FILAMENT_LIVE_PREVIEW_SOLUTION.md` | Filament live preview approach |

---

*Last updated: June 15, 2026*
