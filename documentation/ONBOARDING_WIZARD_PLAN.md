# Onboarding Wizard — Planning Doc

Status: **Design / pre-implementation.** This captures the parameter inventory, the
Shopify auth decision, required OAuth scopes, and the proposed wizard step flow.
PSP credentials are intentionally **out of scope** for the wizard (entered later in
Payment Gateway config).

---

## 1. Goal

After registration + successful subscription payment, redirect the client into an
onboarding wizard that programmatically populates their app config — instead of
dropping them at the login screen to configure everything manually under *App Settings*.
(App preview is a later phase.)

---

## 2. Key architectural decision: Shopify auth model

### Today (manual, to be replaced)
Each tenant is a **custom app the merchant creates inside their own Shopify admin**.
They paste `shopify_client_id` + `shopify_client_secret` per tenant, and
`ShopifyAdminTokenService` exchanges them via a `client_credentials` grant for a
short-lived (≈24h) admin token. This cannot be automated.

### Target (OAuth-first)
Build **one** Shopify app (Partner Dashboard, **Public distribution, kept unlisted** —
installs on any merchant store via the OAuth link without App Store listing). Do **not**
use Custom distribution, which locks the app to a single store. Its
`client_id`/`client_secret` are **global** (live in `.env`, shared by all tenants).
See `ONBOARDING_SETUP_GUIDE.md` for the step-by-step Partner app setup.

Flow:
1. Merchant enters their **shop domain** (the only mandatory manual input).
2. "Connect Shopify" → redirect to **Shopify's own** authorization/consent screen.
   - ⚠️ Never build a form that collects the merchant's Shopify email/password.
     Use Shopify's OAuth redirect (handles 2FA/SSO; required by Shopify terms).
3. Merchant approves requested scopes → Shopify redirects to our callback with a code.
4. Exchange code → **per-merchant offline admin token** (authorization-code grant =
   permanent token, no refresh needed — simpler than the current expiring-token code).
5. With that token, programmatically:
   - generate the **Storefront token** (`storefrontAccessTokenCreate`),
   - read **brand assets** (name / logo / colors) to pre-fill the wizard,
   - read **collections** and create **`flutter-menu`** (`menuCreate`).

### Schema impact
- **Remove** per-tenant `shopify_client_id`, `shopify_client_secret` (become app-level env).
- **Keep** `shopify_shop_domain` (manual), `shopify_admin_token` (from OAuth),
  `shopify_storefront_token` (auto-generated).
- `shopify_admin_token_expires_at` becomes unused for offline tokens (or repurpose).
- Update `ShopifyAdminTokenService` to stop doing `client_credentials` refresh.

---

## 3. Required OAuth scopes (derived from actual code usage)

| Capability in code | Where | Scope |
|---|---|---|
| `orderCreate`, `orderMarkAsPaid`, `getOrder`, `findOrder` | `ShopifyAdminService` | `write_orders`, `read_orders` |
| Generate storefront token | wizard (new) | `unauthenticated_read_product_listings` + related `unauthenticated_*` (delegated by `storefrontAccessTokenCreate`) |
| Create `flutter-menu` | wizard (new) | `write_online_store_navigation` |
| Read collections to build menu | `ShopifyStorefrontService` (storefront) / admin | `read_products` (admin) / storefront product+collection access |
| Read products / collections / brands / cart | `ShopifyStorefrontService` | Storefront API (`unauthenticated_read_product_listings`, `unauthenticated_read_content` for menus, `unauthenticated_write_checkouts` for cart) |
| Read brand assets (logo/colors/name) | wizard (new, Storefront `shop.brand`) | covered by storefront unauthenticated content access |

> **Action item:** confirm exact scope strings against the Shopify app config before
> building — scope names differ slightly between Admin and Storefront APIs and across
> API versions. Request the minimum set that covers the table above.

---

## 4. Parameter inventory

### A. Captured at registration (pre-wizard) — `SubscriptionController::subscribe()`
`full_name`, `email`, `password` → `users`; `company_name`→`tenants.name`,
`phone`, `website`, `email` → `tenants`; package/billing/addons/country → `subscriptions`.
Auto-generated: `tenants.app_key` (UUID), `tenants.status`.

### B. Shopify connection — `tenants` table (OAuth-first)
- `shopify_shop_domain` — **manual** (only hard manual input).
- `shopify_admin_token` — **auto** (OAuth).
- `shopify_storefront_token` — **auto** (`storefrontAccessTokenCreate`).

### C. App basics & branding — `app_configurations.config['basic']`
`app_name` (pre-fill from `shop.name`), `logo` + `primary_color` + `secondary_color`
(pre-fill from Storefront `shop.brand` when populated — editable defaults),
`show_brands`, `sync_level_0_categories`, `use_multiple_home_layouts`,
`show_level_0_tabs`, `show_category_selection_screen`.

### D. Contact — `config['contact']`: `email`, `phone`, `address`
### E. Social — `config['social_media']`: `facebook`, `instagram`, `twitter`, `tiktok`
### F. Static pages — `config['static_pages']`: `privacy_policy`, `return_policy`, `shipping_policy`, `payment_policy`, `about_us`
### G. E-commerce — `config['ecommerce']`: `shipping_promise`, `payment_options_image`, `tabby_enabled`/`tabby_text`, `tamara_enabled`/`tamara_text` (display text only)
### H. Add-ons — `config['addons']`: `whatsapp_chat_number`

### I. Payment gateways — **OUT OF SCOPE for wizard** (configured later)
Per-PSP credentials in `payment_gateways` (source of truth: `PaymentGatewayType` enum).

### J. Content (Phase 2 — likely skip in v1 wizard)
- Introduction screen — `tenants.intro_config` (banner, welcome title/text, feature sections).
- Home layout — `tenants.home_layout` / `home_layouts` table.

### Open gaps (decide before build)
- **Push (OneSignal)** is currently **global env**, not per-tenant. If each app needs
  its own OneSignal app → net-new schema. Decision: in scope?
- **No app-build identifiers** exist (bundle id, package name, app icon/splash, store
  listing). If "MaaS" means generating the real mobile app, these are missing. v1 scope?

---

## 5. Proposed wizard step flow (v1)

Minimum to "finish" and have a working app: **Shopify connected + app name**. Everything
else is progressive / skippable.

1. **Connect Shopify** *(blocking)*
   - Input: shop domain → OAuth redirect → callback stores admin token →
     auto-generate storefront token. Validate connection.
2. **Branding** *(pre-filled)*
   - app_name (from `shop.name`), logo + primary/secondary colors (from `shop.brand`,
     editable). Fallbacks when brand assets empty.
3. **Categories / navigation** *(optional but recommended)*
   - Read collections → merchant selects + orders main categories → `menuCreate` builds
     `flutter-menu` → feeds existing category-sync step.
4. **Store info** *(optional)* — contact, social, static page URLs.
5. **E-commerce display** *(optional)* — shipping promise, payment options image,
   Tabby/Tamara display text.
6. **Finish** → redirect to admin panel (or app preview in a later phase).

Skipped sections remain editable later under *App Settings* (no data loss; same
`AppConfiguration` store).

---

## 6. Next steps
- [ ] Confirm exact Shopify scope strings + create the Partner app (Public distribution, unlisted).
- [ ] Decide OneSignal per-tenant and app-build identifiers scope.
- [x] Build OAuth connect + callback + storefront-token generation. *(Step 1, see §7)*
- [x] Build branding step (name/logo/colors, pre-filled from Shopify brand). *(Step 2, see §8)*
- [x] Expiring offline tokens (Shopify Apr-2026 requirement) + auto-refresh. *(see §7)*
- [x] Build categories step (collections → `flutter-menu`). *(Step 3, see §9)*
- [x] Build store-info + finish steps. *(Steps 4–5, see §10)* — **all 5 wizard steps now built.**
- [ ] Migration: drop per-tenant client_id/secret (legacy custom-app fields).
      `ShopifyAdminTokenService` now refreshes via the OAuth refresh token (done).

---

## 7. Implemented — Step 1: Shopify OAuth connect

**Config**
- `config/services.php` → `services.shopify` (`client_id`, `client_secret`,
  `api_version`, `scopes`). Env keys in `.env.example`:
  `SHOPIFY_APP_CLIENT_ID`, `SHOPIFY_APP_CLIENT_SECRET`, `SHOPIFY_API_VERSION`,
  `SHOPIFY_APP_SCOPES`.

**Code**
- `App\Services\Shopify\ShopifyOAuthService` — domain normalization/validation,
  authorize-URL builder, HMAC verification, code→token exchange,
  `storefrontAccessTokenCreate`, and credential persistence.
- `App\Http\Controllers\Onboarding\ShopifyOnboardingController` — `show` / `connect`
  / `callback`.
- Routes (`routes/web.php`, prefix `onboarding`): `onboarding.shopify.show` (signed),
  `onboarding.shopify.connect` (POST), `onboarding.shopify.callback`.
- View: `resources/views/onboarding/shopify.blade.php` (connect + connected states,
  step indicator, Oliv branding).
- Handoff: `SubscriptionController::success()` resolves the tenant from the Stripe
  session and links into the signed wizard URL; success page CTA updated.

**Security**
- `show` is a **signed** route (no tenant enumeration).
- OAuth `state` nonce bound to tenant+shop in session; verified on callback.
- Shopify `hmac` verified with the app secret.
- Shop domain restricted to `*.myshopify.com`.

**Expiring offline tokens (Shopify requirement, Apr 2026+)**
- Shopify **rejects non-expiring tokens** for the Admin API on new public apps
  (`"Non-expiring access tokens are no longer accepted"`, HTTP 403). This broke
  `storefrontAccessTokenCreate` until fixed.
- `exchangeCodeForToken` now sends **`expiring=1`** → Shopify returns an `access_token`
  (~1h), a `refresh_token` (~90d), and `expires_in`.
- Persisted on the tenant: `shopify_admin_token`, `shopify_admin_token_expires_at`,
  and **`shopify_admin_refresh_token`** (new column — migration
  `..._add_shopify_admin_refresh_token_to_tenants_table`).
- `ShopifyAdminTokenService` now auto-refreshes the admin token (5-min buffer) via
  `grant_type=refresh_token` + global app credentials (replacing the old
  `client_credentials` refresh). `ShopifyOAuthService::refreshAccessToken()` added.

**Verified end-to-end** (local, against a Partner-org dev store via Expose):
connect → expiring admin token → **storefront token created** → branding pre-fills the
app name from the store. Required a **Public** Shopify app (Custom distribution can't
drive the standalone OAuth flow — see `ONBOARDING_SETUP_GUIDE.md` §5c).

**Follow-ups**
- After-connect / after-branding "Continue" routes to `/admin/login` until the
  Categories step exists.
- Per-tenant `shopify_client_id`/`secret` columns still exist (legacy) — drop once the
  custom-app path is fully retired.
- Production: re-add `read_orders`/`write_orders` + complete PCD; submit app for review.
- (done) Automated tests — `tests/Feature/ShopifyOnboardingTest.php` (incl. token
  exchange with `expiring=1` and admin-token refresh).

---

## 8. Implemented — Step 2: Branding

**Code**
- `ShopifyStorefrontService::getBrandAssets()` — reads `shop.name` + `shop.brand`
  (logo, primary/secondary colors) via the Storefront API; every field may be null.
- `App\Http\Controllers\Onboarding\BrandingController` — `show` / `save`.
  - `show` requires Shopify to be connected (else bounces back to Step 1), pre-fills
    from saved `AppConfiguration['basic']` first, then Shopify brand assets, then
    Oliv defaults.
  - `save` validates and writes `app_name`, `primary_color`, `secondary_color`, `logo`
    into `AppConfiguration['basic']`. Logo precedence: uploaded file > download of the
    suggested Shopify brand logo > keep existing. Uploaded/downloaded logos go to the
    `public` disk under `app-settings/` (same place the Filament *App Settings* page uses).
- Routes: `onboarding.branding.show` (signed), `onboarding.branding.save` (POST).
- View: `resources/views/onboarding/branding.blade.php` — editable form + live phone
  preview (app name, colors, logo update in real time).
- Step 1's callback + connected-state CTA now continue into Step 2.

**Flow now:** payment success → Step 1 (connect) → Step 2 (branding) → Step 3 (categories)
→ `/admin/login` (store-info / finish steps not built yet).

**Tests:** `tests/Feature/BrandingOnboardingTest.php`
(signed-route guard, connection guard, brand-asset pre-fill, validation,
config persistence, uploaded-logo + downloaded-logo paths).

**Follow-ups**
- Consider downloading/storing the logo as a normalized size; currently stored as-is.

---

## 9. Implemented — Step 3: Categories (build `flutter-menu`)

**Code**
- `ShopifyAdminService` — new methods: `getCollections()` (list store collections),
  `getMenuIdByHandle()`, and `upsertFlutterMenu()` (creates via `menuCreate` or updates
  via `menuUpdate`, with `COLLECTION` menu items pointing at the chosen collection GIDs).
  Requires the `write_online_store_navigation` scope (already requested).
- `App\Http\Controllers\Onboarding\CategoriesController` — `show` / `save`.
  - `show` requires Shopify connected (else bounces to Step 1), lists collections, and
    pre-checks any previously saved selection.
  - `save` re-fetches collections from Shopify (trusted titles/GIDs), orders them by the
    submitted selection, builds/updates **`flutter-menu`**, then persists
    `AppConfiguration['categories']['collection_ids']` and sets
    `basic.sync_level_0_categories = true` (the flag the app uses to surface category tabs).
- Routes: `onboarding.categories.show` (signed), `onboarding.categories.save` (POST).
- View: `resources/views/onboarding/categories.blade.php` — collection checklist with
  thumbnails, step indicator (Step 3 active), Oliv branding.
- Step 2's "Save & continue" now flows into Step 3.

**Tests:** `tests/Feature/CategoriesOnboardingTest.php` (signed-route + connection guards,
collection listing, required-selection validation, `flutter-menu` build with order
preserved + config persistence, Shopify-failure handling). `ShopifyAdminService` is mocked.

**Follow-ups**
- No drag-reorder yet — category order follows the (alphabetical) list order / submit
  order. Add explicit reordering later if needed.
- After building `flutter-menu`, the existing `CategorySyncService` (App Settings flow)
  can sync categories → home layouts; not yet auto-triggered from the wizard.
- The connected app links `flutter-menu` items to collections by GID; deleting a
  collection in Shopify later will need a re-sync.

---

## 10. Implemented — Steps 4 & 5: Store info + Finish

**Code**
- `App\Http\Controllers\Onboarding\StoreInfoController` — `show` / `save`.
  - All fields **optional** (contact email/phone/address, social URLs, policy URLs);
    a "Skip for now" button clears and submits.
  - `save` validates (email/url formats), writes `contact` / `social_media` /
    `static_pages` into `AppConfiguration`, and stamps
    `AppConfiguration['onboarding']['completed_at']` to mark onboarding done.
- `App\Http\Controllers\Onboarding\FinishController` — `show` only; renders the
  completion screen (app name, storefront-ready indicator) → CTA to `/admin/login`.
- Routes: `onboarding.store-info.show` (signed) / `.save` (POST),
  `onboarding.finish.show` (signed).
- Views: `resources/views/onboarding/store-info.blade.php` (Step 4 active),
  `resources/views/onboarding/finish.blade.php` (all steps complete).
- Step 3's "Save & continue" → Step 4 → Step 5 (Finish) → `/admin/login`.

**Tests:** `tests/Feature/StoreInfoOnboardingTest.php` (guards, render, persistence +
onboarding-complete stamp, skip-all, validation; Finish render + signed guard).

**Full wizard flow now (all 5 steps live):**
`payment success → Connect Shopify → Branding → Categories → Store info → Finish → dashboard login`

**Remaining for production launch** (tracked elsewhere, not wizard work):
- Re-add `read_orders`/`write_orders` + complete the PCD declaration.
- Submit the Shopify app for **review** (required for real merchant stores).
- Optional polish: category drag-reorder; auto-trigger `CategorySyncService`; normalize
  uploaded logo size; drop legacy per-tenant `shopify_client_id`/`secret` columns.
