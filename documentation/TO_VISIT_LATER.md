# To Visit Later

Parking lot for decisions/investigations deferred during the onboarding-wizard design.
Not blocking v1. Revisit before the relevant feature is built.

---

## 1. Per-tenant push notifications (OneSignal)

**Current state:** OneSignal is configured **globally** via env
(`ONESIGNAL_APP_ID`, `ONESIGNAL_API_KEY` in `config/services.php`). Not per-tenant.

**Original idea:** one OneSignal account + a segment per app.

**Caveat to investigate:** OneSignal *segments* are audience filters **inside a single
OneSignal app**, and one OneSignal app maps to exactly **one iOS bundle id + one Android
package + one FCM project**. Because each tenant ships a **separate native binary** (its
own bundle id / package, built manually by our dev), they cannot share one OneSignal app
— APNs/FCM credentials are per-binary. So segments do **not** isolate tenants.

**Likely direction:** one **OneSignal app per tenant**, all under a single OneSignal
**Organization/account** (one login, one bill). Store per-tenant `onesignal_app_id` +
REST API key (net-new schema). Use segments only *within* a tenant (e.g. by country /
category interest).

**To decide later:**
- [ ] Confirm OneSignal Organizations model + per-app credential requirement.
- [ ] Schema: add `onesignal_app_id` / `onesignal_api_key` to tenants (encrypted)?
- [ ] Who provisions the OneSignal app per tenant — manual by dev, or automated via
      OneSignal API during onboarding?
- [ ] Where push config slots into the wizard (or stays a post-launch admin step).

---

## 2. App-build identifiers / store assets

**Current state:** the mobile app is **built manually by our dev** outside this system,
so the backend stores **no** build/store identifiers (bundle id, Android package name,
app icon, splash screen, App Store / Play Store listing metadata).

**Why parked:** not needed while builds are manual. Becomes relevant only if we move
toward automated/templated app generation ("MaaS" in the fuller sense).

**To decide later:**
- [ ] Does the platform ever need to *know* the bundle id / package name (e.g. to tie
      OneSignal apps, deep links, or Apple/Google Pay merchant domains to a tenant)?
- [ ] If app generation is automated later: where do icon/splash/store metadata get
      collected — extend the wizard, or a separate "build config" surface?
- [ ] Relationship to PSP wallet config (Apple Pay verified domain, Google Pay merchant
      name) which already overlaps with app identity.

---

## 3. (add new items here as they come up)
