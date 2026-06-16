# Implementation Plan: Shopify Onboarding Wizard

## Overview

Implement an 8-step Livewire onboarding wizard that connects to Shopify via OAuth, imports store data asynchronously, provides AI-powered feature/theme recommendations, and generates a full app configuration. The implementation follows the existing service-layer and queue-job patterns in the Laravel 12 codebase.

## Tasks

- [x] 1. Database schema and models
  - [x] 1.1 Create migration for `onboarding_sessions` table
    - Create `database/migrations/xxxx_create_onboarding_sessions_table.php` with columns: id, tenant_id (FK), platform, step, shopify_domain, imported_data (JSON), wizard_payload (JSON), ai_recommended (JSON), completed_at, timestamps
    - Add index on `tenant_id`
    - _Requirements: 12.1_

  - [x] 1.2 Create migration for `app_configs` table
    - Create `database/migrations/xxxx_create_app_configs_table.php` with columns: id, tenant_id (FK, unique), app_name, app_icon_url, splash_url, primary_color, secondary_color, font_family, theme_id (FK nullable), home_layout (JSON), navigation (JSON), publish_status, bundle_id_ios, bundle_id_android, timestamps
    - _Requirements: 12.2_

  - [x] 1.3 Create migration for `feature_flags` table
    - Create `database/migrations/xxxx_create_feature_flags_table.php` with columns: id, tenant_id (FK), feature_key, enabled, config (JSON), timestamps
    - Add UNIQUE constraint on (tenant_id, feature_key)
    - _Requirements: 12.3_

  - [x] 1.4 Create migration for `app_themes` table and seeder
    - Create `database/migrations/xxxx_create_app_themes_table.php` with columns: id, name, default_config (JSON), timestamps
    - Create `database/seeders/AppThemeSeeder.php` seeding 6 themes: Minimal, Bold, Classic, Modern, Luxe, Fresh
    - _Requirements: 12.4_

  - [x] 1.5 Add Shopify credential columns to Tenant model
    - Create migration adding `shopify_shop_domain`, `shopify_admin_token`, `shopify_client_id`, `shopify_client_secret` to `tenants` table (if not already present from existing shopify oauth migration)
    - Update `App\Models\Tenant` with `$hidden` array containing `shopify_admin_token` and `shopify_client_secret`
    - _Requirements: 12.5, 2.8_

  - [x] 1.6 Create Eloquent models for OnboardingSession, AppConfig, FeatureFlag, AppTheme
    - Create `App\Models\OnboardingSession` with fillable, casts (JSON fields, datetime, integer), and tenant relationship
    - Create `App\Models\AppConfig` with fillable, casts, tenant and theme relationships
    - Create `App\Models\FeatureFlag` with fillable, casts, tenant relationship
    - Create `App\Models\AppTheme` with fillable and casts
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

- [x] 2. StoreAdapter interface and implementations
  - [x] 2.1 Create StoreAdapter interface
    - Create `app/Contracts/StoreAdapter.php` defining `fetchStoreInfo`, `fetchCatalog`, `fetchBranding` methods with typed array returns
    - _Requirements: 11.1_

  - [x] 2.2 Implement ShopifyAdapter
    - Create `app/Services/Onboarding/ShopifyAdapter.php` implementing StoreAdapter
    - Inject existing `ShopifyAdminService` and `ShopifyAdminTokenService`
    - Implement `fetchStoreInfo` via REST shop.json endpoint
    - Implement `fetchCatalog` via GraphQL query (productsCount, collections, products)
    - Implement `fetchBranding` via GraphQL brand query
    - _Requirements: 11.2_

  - [x] 2.3 Implement ManualAdapter
    - Create `app/Services/Onboarding/ManualAdapter.php` implementing StoreAdapter
    - Return empty/default values for all three methods
    - _Requirements: 11.3_

  - [ ]* 2.4 Write property test for ManualAdapter structural validity
    - **Property 14: ManualAdapter returns structurally valid defaults**
    - **Validates: Requirements 11.3**

- [x] 3. OAuth service and security
  - [x] 3.1 Create OAuthValidationException
    - Create `app/Exceptions/OAuthValidationException.php` extending RuntimeException
    - _Requirements: 2.2, 2.3, 2.4, 2.5_

  - [x] 3.2 Implement ShopifyOAuthService
    - Create `app/Services/Onboarding/ShopifyOAuthService.php`
    - Implement `buildAuthorizationUrl` with state parameter generation and session persistence
    - Implement `validateCallback` orchestrating HMAC, state, and domain checks
    - Implement `validateHmac` with `hash_hmac('sha256', ...)` and `hash_equals`
    - Implement `validateState` with `hash_equals` comparison
    - Implement `validateShopDomain` with regex pattern `^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$`
    - Implement `validateScopes` checking all required scopes are present
    - Implement `exchangeCodeForToken` with `Crypt::encryptString` for AES-256-GCM storage
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.10_

  - [ ]* 3.3 Write property test for HMAC validation
    - **Property 2: HMAC validation rejects tampered signatures**
    - **Validates: Requirements 2.2, 2.3**

  - [ ]* 3.4 Write property test for state parameter validation
    - **Property 3: State parameter validation rejects mismatches**
    - **Validates: Requirements 2.4**

  - [ ]* 3.5 Write property test for shop domain validation
    - **Property 4: Shop domain validation accepts only valid Shopify domains**
    - **Validates: Requirements 2.5**

  - [ ]* 3.6 Write property test for token encryption round-trip
    - **Property 5: Token encryption round-trip preserves the original value**
    - **Validates: Requirements 2.7**

  - [ ]* 3.7 Write property test for token exclusion from serialization
    - **Property 6: Token exclusion from serialized Tenant output**
    - **Validates: Requirements 2.8**

  - [ ]* 3.8 Write property test for OAuth scope validation
    - **Property 7: OAuth scope validation rejects incomplete scope sets**
    - **Validates: Requirements 2.10**

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Import job and AI recommendation engine
  - [x] 5.1 Create ImportShopifyDataJob
    - Create `app/Jobs/ImportShopifyDataJob.php` implementing ShouldQueue
    - Configure 3 retries with backoff [10, 30, 60]
    - Fetch store info, catalog, and branding via ShopifyAdapter
    - Store results in OnboardingSession `imported_data` JSON field
    - Trigger AI recommendation generation on completion
    - Implement `failed()` method marking session with error
    - _Requirements: 3.1, 3.2, 3.4_

  - [x] 5.2 Implement AIRecommendationEngine
    - Create `app/Services/Onboarding/AIRecommendationEngine.php`
    - Implement industry-to-feature mapping table (fashion, food_and_drink, electronics, etc.)
    - Implement industry-to-theme mapping
    - Implement signal boosters (high_product_count, has_categories, has_logo)
    - Implement `generateRecommendations` storing results in `ai_recommended` field
    - Implement `resolveIndustry` with primary (Shopify field) and secondary (product type keywords) resolution
    - Implement `scoreFeatures` combining industry features + boosters
    - Implement `scoreTheme` with dark-color detection for Luxe boost
    - _Requirements: 6.1, 6.5, 7.2_

  - [ ]* 5.3 Write property test for AI recommendation engine validity
    - **Property 10: AI recommendation engine produces valid output for any industry**
    - **Validates: Requirements 6.1, 6.2, 7.2**

- [x] 6. AppConfigGenerator service
  - [x] 6.1 Implement AppConfigGenerator
    - Create `app/Services/Onboarding/AppConfigGenerator.php`
    - Implement `generate` method creating AppConfig via `updateOrCreate`
    - Create FeatureFlag records for each feature selection
    - Generate bundle IDs from tenant name
    - Build home layout from theme defaults
    - Build navigation tabs (with conditional wishlist tab)
    - Mark session `completed_at`
    - _Requirements: 8.2, 8.3, 8.4, 8.5_

  - [ ]* 6.2 Write property test for FeatureFlag generation
    - **Property 11: App generation creates one FeatureFlag per selected feature**
    - **Validates: Requirements 8.3, 8.5**

- [x] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Routes and OnboardingController
  - [x] 8.1 Create OnboardingController
    - Create `app/Http/Controllers/OnboardingController.php`
    - Implement `shopifyConnect` method: validate domain input, store on session, build OAuth URL via ShopifyOAuthService, redirect
    - Implement `shopifyCallback` method: validate HMAC/state/domain/scopes, exchange code for token, dispatch ImportShopifyDataJob
    - Implement `importStatus` method: return JSON with import readiness status for polling
    - Handle error cases: HMAC failure → 401, state mismatch → 401, invalid domain → 401, missing scopes → error view
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.9, 2.10, 3.3_

  - [x] 8.2 Register web routes
    - Add routes to `routes/web.php`: GET /onboarding (Livewire), GET /onboarding/shopify/callback, POST /onboarding/shopify/connect, GET /onboarding/import-status
    - All routes within `auth` middleware group
    - _Requirements: 1.1, 2.1_

- [x] 9. Livewire wizard component and views
  - [x] 9.1 Create OnboardingWizard Livewire component
    - Create `app/Livewire/OnboardingWizard.php` as full-page Livewire component
    - Implement `mount()` with session first-or-create and hydration from existing session
    - Implement `nextStep()` with per-step validation and persistence
    - Implement `previousStep()` for backward navigation
    - Implement step-specific validation rules (platform required, store name required, branding color regex)
    - Implement `hydrateFromSession()` to restore state on page load
    - Implement `persistCurrentStep()` to save step data to OnboardingSession
    - Track `currentStep`, expose step data as public properties
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 4.2, 4.4, 5.5, 6.4, 7.4, 10.1, 10.2, 10.3_

  - [x] 9.2 Create standalone onboarding layout
    - Create `resources/views/layouts/onboarding.blade.php` — minimal standalone layout (no Filament chrome)
    - Include Livewire scripts/styles, Tailwind, progress indicator
    - _Requirements: 1.1_

  - [x] 9.3 Create Blade views for wizard steps
    - Create `resources/views/livewire/onboarding-wizard.blade.php` (main step router)
    - Create `resources/views/livewire/steps/platform-select.blade.php` (Step 1: Shopify vs Manual)
    - Create `resources/views/livewire/steps/shopify-connect.blade.php` (Step 2: domain input + connect button)
    - Create `resources/views/livewire/steps/data-review.blade.php` (Step 3: loading state + editable fields)
    - Create `resources/views/livewire/steps/branding.blade.php` (Step 4: app name, icon, colors, font, splash)
    - Create `resources/views/livewire/steps/features.blade.php` (Step 5: feature toggles with AI pre-selection)
    - Create `resources/views/livewire/steps/theme-select.blade.php` (Step 6: 6 theme cards with AI highlight)
    - Create `resources/views/livewire/steps/review-generate.blade.php` (Step 7: summary + generate button)
    - Create `resources/views/livewire/steps/completion.blade.php` (Step 8: confirmation + dashboard link)
    - _Requirements: 1.1, 1.2, 1.3, 3.3, 4.1, 4.2, 5.4, 6.2, 6.3, 7.1, 7.3, 8.1, 9.1, 9.2_

  - [ ]* 9.4 Write property test for session payload round-trip
    - **Property 1: OnboardingSession payload persistence round-trip**
    - **Validates: Requirements 1.4, 3.2, 4.4, 5.5, 6.4, 7.4, 10.1**

  - [ ]* 9.5 Write property test for wizard resumability
    - **Property 13: Wizard resumability restores correct step**
    - **Validates: Requirements 10.2**

- [x] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Integration wiring and generation flow
  - [x] 11.1 Wire generate action in OnboardingWizard
    - Add `generate()` method to OnboardingWizard component
    - Call `AppConfigGenerator::generate()` to create AppConfig and FeatureFlags
    - Dispatch tenant-provisioning and catalog-sync jobs (existing patterns)
    - Advance to Step 8 (completion)
    - Ensure generation completes within 2 seconds (synchronous config creation, async jobs for heavy work)
    - _Requirements: 8.2, 8.3, 8.4, 8.5, 9.1_

  - [x] 11.2 Wire import polling in data-review step
    - Add Livewire polling (`wire:poll.2s`) to Step 3 view checking import status
    - When import ready, populate storeData from session `imported_data`
    - When import fails after retries, show error + manual entry fallback
    - _Requirements: 3.3, 3.4, 4.1_

  - [x] 11.3 Wire OAuth redirect flow
    - In Step 2 view, form POSTs shopify domain to `onboarding.shopify.connect`
    - On successful callback, redirect back to wizard at Step 3
    - On callback failure, redirect back with error flash message
    - _Requirements: 2.1, 2.6, 2.9_

  - [ ]* 11.4 Write property test for data import mapping
    - **Property 8: Data mapping from import to wizard preserves all fields**
    - **Validates: Requirements 4.1, 5.1, 5.2, 5.3**

  - [ ]* 11.5 Write property test for zero manual fields with complete import
    - **Property 9: Complete Shopify import requires zero manual fields**
    - **Validates: Requirements 4.3**

  - [ ]* 11.6 Write property test for review summary completeness
    - **Property 12: Review summary contains all configured sections**
    - **Validates: Requirements 8.1**

- [x] 12. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties using Eris (already in composer.json)
- Unit tests validate specific examples and edge cases
- The cipher config (`aes-256-gcm`) in `config/app.php` must be verified/set before token encryption tests
- Existing `ShopifyAdminService` and `ShopifyAdminTokenService` are reused by ShopifyAdapter — no duplication
- The wizard is standalone at `/onboarding` and does not interfere with the existing Filament admin panel

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["1.5", "1.6", "2.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "3.1"] },
    { "id": 3, "tasks": ["2.4", "3.2"] },
    { "id": 4, "tasks": ["3.3", "3.4", "3.5", "3.6", "3.7", "3.8"] },
    { "id": 5, "tasks": ["5.1", "5.2"] },
    { "id": 6, "tasks": ["5.3", "6.1"] },
    { "id": 7, "tasks": ["6.2", "8.1"] },
    { "id": 8, "tasks": ["8.2", "9.1"] },
    { "id": 9, "tasks": ["9.2", "9.3"] },
    { "id": 10, "tasks": ["9.4", "9.5", "11.1"] },
    { "id": 11, "tasks": ["11.2", "11.3"] },
    { "id": 12, "tasks": ["11.4", "11.5", "11.6"] }
  ]
}
```
