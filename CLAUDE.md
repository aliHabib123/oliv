# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Oliv-Tech is a Laravel 12 multi-tenant SaaS that lets businesses run mobile e-commerce apps backed by Shopify. There are two distinct surfaces in one codebase:

1. **Mobile app API** (`routes/api.php`, `app/Http/Controllers/Api/`) — the JSON backend consumed by tenant mobile apps. Catalog/orders live in Shopify; this layer adds carts, native checkout, multiple regional payment service providers (PSPs), push notifications, and per-tenant content/config.
2. **Filament admin panel + subscription billing** (`routes/web.php`, `app/Filament/`, `SubscriptionController`) — staff manage tenants/packages/addons; tenants sign up and pay for the *platform subscription* via Stripe. This Stripe billing is unrelated to the customer-facing checkout PSPs in surface #1.

## Commands

```bash
composer run dev          # server + queue worker + pail logs + vite, all concurrently
composer test             # clears config, then runs the full suite
php artisan test --filter PaymentApiTest        # single test class
php artisan test --filter=test_method_name      # single test method
./vendor/bin/pint         # format PHP (Laravel Pint) — run before committing
php artisan l5-swagger:generate                  # regenerate OpenAPI docs (served at /api/documentation)
php artisan migrate && php artisan db:seed       # seeders: CountriesSeeder, PricingSeeder, etc.
npm run dev / npm run build                       # Vite + Tailwind 4 assets
```

Tests run against an in-memory SQLite DB (see `phpunit.xml`). The app itself uses MySQL (`.env`). PHP 8.2+ (composer platform pinned to 8.3.30).

## Multi-tenancy & request auth

The mobile API has **no per-user auth**. Every `routes/api.php` route is wrapped in `ResolveTenantFromAppKey` middleware: it reads the `X-App-Key` header, looks up the active `Tenant`, and attaches it via `$request->attributes->set('tenant', ...)`. Controllers read it with `$request->attributes->get('tenant')`. A missing key is allowed through with a null tenant; an invalid/inactive key returns 404.

**Exception:** PSP webhooks (`/webhooks/tabby`, `/webhooks/tamara`) sit *outside* the tenant middleware because there is no app key on an inbound webhook — the tenant is resolved from the payload instead. Keep new PSP webhooks out of the middleware group too.

`app_key` is a UUID auto-generated on `Tenant` creation (see `Tenant::booted()`).

## Payment gateway architecture

This is the most non-obvious part of the codebase. Multiple PSPs (Tabby, Tamara, Stripe, PayPal, Telr, Amazon Payment Services/PayFort, Tap) are configured **per tenant**.

- `app/Enums/PaymentGatewayType.php` is the **single source of truth** for every PSP: its credential field definitions, which fields are `public` (safe to expose to the mobile app) vs server-only, sandbox/live API URLs, and Apple Pay / Google Pay support and fields. When adding or changing a PSP, edit this enum first — models and the admin UI derive their behavior from it.
- `app/Models/PaymentGateway.php` stores a tenant's credentials. `config` and `wallet_config` are cast `encrypted:array`. Use `getPublicConfig()` to expose credentials to clients (filters to the enum's public fields) and `getServerConfig()` for server-side calls. Never return raw `config`.
- Per-PSP service classes live in `app/Services/Payment/` (`TabbyService`, `TamaraService`, `TapService`, `AmazonPaymentService`, `WebhookVerificationService`), each with a matching `Api/<Psp>Controller`.

## Native checkout flow

SDK-based: the mobile app drives PSP payment, the backend records and verifies. See `documentation/CHECKOUT_PAYMENT_FLOW.md` and `.kiro/specs/native-checkout/`.

```
POST /v1/checkout/session       → CheckoutSession (needs Idempotency-Key header)
   mobile SDK collects payment, gets psp_transaction_id
POST /v1/checkout/record-payment → PaymentTransaction (pending)
POST /v1/checkout/confirm        → verify with PSP, create Shopify order, clear cart
```

`CheckoutSessionService` orchestrates this; `CreateShopifyOrderJob` (queued) creates the Shopify order; `ShopifyOrderMapping` links sessions to orders. Stale sessions are reaped by `checkout:expire-sessions`, scheduled every 5 minutes (`routes/console.php`).

## Other subsystems

- **Shopify** (`app/Services/Shopify/`): `ShopifyStorefrontService` (catalog), `ShopifyAdminService` (orders), `ShopifyAdminTokenService` (admin token refresh; tokens stored on `Tenant` with an expiry). Inbound Shopify webhooks in `ShopifyWebhookController`.
- **Multi-country pricing**: `Country`, `PackagePricing`, `AddonPricing` models + `CountryPricingService` resolve subscription prices and Stripe price IDs per country (US/AE/SA/QA/GB/EU). See `COUNTRY_PRICING_IMPLEMENTATION.md`.
- **Push**: `app/Services/OneSignal/OneSignalService.php`, `DeviceToken` model.
- **Cart**: `app/Services/Cart/CartService.php`, with `Cart`/`CartEvent`/`CheckoutEvent` logging activity.

## Deployment

CI deploys on push to the **`release`** branch only (`.github/workflows/deploy.yml`) via SSH: `git reset --hard origin/release`, `composer install --no-dev`, then regenerates Swagger and migrates. `main` is the development/PR branch. Note `darkaonline/l5-swagger` is in `require` (not `require-dev`) so docs generate in production.

## Reference docs in-repo

`PROJECT_INDEX.md` (full model/route/controller map), `API_DOCUMENTATION.md` (endpoint request/response examples), `documentation/` (checkout flow, Apple Pay setup, mobile SDK guide, Postman guide), `.kiro/specs/` (native-checkout feature specs).
