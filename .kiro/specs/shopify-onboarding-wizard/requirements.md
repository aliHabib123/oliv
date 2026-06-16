# Requirements Document

## Introduction

An 8-step onboarding wizard that guides new merchants from account creation to a fully configured mobile app in under 5 minutes. The wizard connects to Shopify via OAuth, auto-imports store data (branding, catalog, configuration), provides AI-powered recommendations for features and themes, and queues an app build — minimizing manual input by leveraging Shopify store data as the primary source.

## Glossary

- **Wizard**: The standalone Livewire multi-step onboarding interface served at `/onboarding` outside the Filament admin panel
- **Merchant**: A newly registered user completing the onboarding process to configure their mobile app
- **Tenant**: The Oliv platform record representing a merchant's store, holding Shopify credentials and app configuration
- **StoreAdapter**: An interface abstracting platform-specific data retrieval (ShopifyAdapter, ManualAdapter)
- **ShopifyAdapter**: The StoreAdapter implementation wrapping the existing ShopifyAdminService for Shopify API interactions
- **ManualAdapter**: The StoreAdapter implementation providing a fallback form-based data entry flow
- **OnboardingSession**: A server-side persisted record tracking wizard progress, imported data, and AI recommendations
- **AppConfig**: The configuration record defining a tenant's mobile app branding, theme, layout, and bundle identifiers
- **FeatureFlag**: A per-tenant toggle enabling or disabling specific app capabilities
- **AppTheme**: A predefined design template with default configuration for mobile app styling
- **ImportJob**: An asynchronous queue job that fetches store info, catalog, and branding from Shopify in parallel
- **AIRecommendationEngine**: The background process that maps store industry signals to feature and theme recommendations

## Requirements

### Requirement 1: Platform Selection

**User Story:** As a merchant, I want to select my e-commerce platform so that the wizard can auto-import my store data or provide manual entry.

#### Acceptance Criteria

1. WHEN the Merchant opens the Wizard at `/onboarding`, THE Wizard SHALL display Step 1 with platform options: Shopify (recommended) and Manual entry.
2. WHEN the Merchant selects Shopify, THE Wizard SHALL proceed to the Shopify OAuth connection step.
3. WHEN the Merchant selects Manual entry, THE Wizard SHALL proceed to the data review step with empty editable fields using the ManualAdapter.
4. THE Wizard SHALL persist the platform selection to the OnboardingSession record server-side.

### Requirement 2: Shopify OAuth Connection

**User Story:** As a merchant, I want to connect my Shopify store with one click so that the system can import my store data automatically.

#### Acceptance Criteria

1. WHEN the Merchant initiates Shopify connection, THE Wizard SHALL redirect to Shopify OAuth with read-only scopes and a cryptographically random state parameter.
2. WHEN the OAuth callback is received, THE Wizard SHALL validate the HMAC signature using the Shopify client secret.
3. IF the HMAC validation fails, THEN THE Wizard SHALL reject the callback with HTTP 401 and display an error message to the Merchant.
4. WHEN the OAuth callback is received, THE Wizard SHALL validate the state parameter matches the value stored in the OnboardingSession.
5. WHEN the OAuth callback is received, THE Wizard SHALL validate the shop domain matches a valid `*.myshopify.com` pattern.
6. WHEN all OAuth validations pass, THE Wizard SHALL exchange the authorization code for an access token and store the encrypted token on the Tenant record.
7. THE Wizard SHALL encrypt the Shopify access token using AES-256-GCM before storing the token on the Tenant record.
8. THE Wizard SHALL ensure the access token value is excluded from application logs, HTTP responses, and frontend-rendered content.
9. WHEN a valid token is obtained, THE Wizard SHALL dispatch the ImportJob to fetch store info, catalog, and branding in parallel.
10. WHEN the OAuth scopes returned by Shopify do not include all requested read-only scopes, THE Wizard SHALL display an error requesting the Merchant to re-authorize with correct permissions.

### Requirement 3: Async Data Import

**User Story:** As a merchant, I want my store data imported in the background so that I can proceed without waiting.

#### Acceptance Criteria

1. WHEN the ImportJob is dispatched, THE ImportJob SHALL fetch store information, product catalog, and branding assets from Shopify in parallel.
2. WHEN the ImportJob completes, THE OnboardingSession SHALL store the imported data in the `imported_data` JSON field.
3. WHEN the Merchant reaches Step 3 before the ImportJob completes, THE Wizard SHALL display a loading state until data is available.
4. IF the ImportJob fails after 3 retry attempts, THEN THE Wizard SHALL notify the Merchant and allow manual data entry as a fallback.

### Requirement 4: Data Review

**User Story:** As a merchant, I want to review and edit my auto-imported store data so that I can correct any inaccuracies before app generation.

#### Acceptance Criteria

1. WHEN the Merchant reaches Step 3 with a Shopify connection, THE Wizard SHALL pre-fill all fields from the imported Shopify data.
2. THE Wizard SHALL make every pre-filled field editable by the Merchant.
3. WHILE the Merchant is connected via Shopify, THE Wizard SHALL require zero manual field entries to proceed to the next step.
4. WHEN the Merchant modifies a pre-filled field, THE Wizard SHALL persist the updated value to the OnboardingSession `wizard_payload` field.

### Requirement 5: App Branding Configuration

**User Story:** As a merchant, I want my app branding pre-configured from my Shopify store so that I can quickly customize my app's appearance.

#### Acceptance Criteria

1. WHEN the Merchant reaches Step 4, THE Wizard SHALL pre-fill the app name from the Shopify store name.
2. WHEN the Merchant reaches Step 4, THE Wizard SHALL pre-fill the app icon from the Shopify store logo URL.
3. WHEN the Merchant reaches Step 4, THE Wizard SHALL pre-fill primary and secondary colors from the Shopify theme color configuration.
4. THE Wizard SHALL allow the Merchant to upload a custom app icon, override colors, select a font family, and configure a splash screen.
5. WHEN the Merchant confirms branding choices, THE Wizard SHALL persist all branding values to the OnboardingSession `wizard_payload` field.

### Requirement 6: Feature Selection

**User Story:** As a merchant, I want AI-recommended features pre-selected so that I get relevant capabilities without manual configuration.

#### Acceptance Criteria

1. THE AIRecommendationEngine SHALL generate feature recommendations based on store industry mapping and signal boosters during Step 3 processing.
2. WHEN the Merchant reaches Step 5, THE Wizard SHALL display available features with AI-recommended features pre-toggled as enabled.
3. THE Wizard SHALL allow the Merchant to enable or disable any feature toggle regardless of AI recommendation.
4. WHEN the Merchant confirms feature selections, THE Wizard SHALL persist selections to the OnboardingSession `ai_recommended` field.
5. THE AIRecommendationEngine SHALL complete processing in the background so the Merchant does not experience wait time on Step 5.

### Requirement 7: Theme and Design Selection

**User Story:** As a merchant, I want to choose from curated design templates with an AI recommendation so that my app has a professional appearance.

#### Acceptance Criteria

1. THE Wizard SHALL display 6 predefined AppTheme templates on Step 6.
2. THE AIRecommendationEngine SHALL recommend one AppTheme based on store industry and branding signals.
3. WHEN the Merchant reaches Step 6, THE Wizard SHALL visually highlight the AI-recommended AppTheme.
4. WHEN the Merchant selects an AppTheme, THE Wizard SHALL persist the selected theme ID to the OnboardingSession `wizard_payload` field.

### Requirement 8: Review and Generate

**User Story:** As a merchant, I want to review my complete configuration and generate my app with one click so that the process is fast and clear.

#### Acceptance Criteria

1. WHEN the Merchant reaches Step 7, THE Wizard SHALL display a summary of all configured settings: platform, branding, features, and theme.
2. WHEN the Merchant clicks the generate button, THE Wizard SHALL create the AppConfig record within 2 seconds.
3. WHEN the Merchant clicks the generate button, THE Wizard SHALL create FeatureFlag records for each selected feature.
4. WHEN the Merchant clicks the generate button, THE Wizard SHALL dispatch tenant-provisioning and catalog-sync queue jobs.
5. WHEN the Merchant clicks the generate button, THE Wizard SHALL mark the OnboardingSession `completed_at` timestamp.

### Requirement 9: Completion and Redirect

**User Story:** As a merchant, I want confirmation that my app build is queued and access to my dashboard so that I know the process succeeded.

#### Acceptance Criteria

1. WHEN app generation jobs are dispatched, THE Wizard SHALL display Step 8 confirming the build is queued.
2. WHEN Step 8 is displayed, THE Wizard SHALL provide a redirect link to the Filament admin dashboard.

### Requirement 10: Wizard State Persistence and Resumability

**User Story:** As a merchant, I want my wizard progress saved so that I can close the browser and return to where I left off.

#### Acceptance Criteria

1. THE Wizard SHALL persist the current step number to the OnboardingSession after each step transition.
2. WHEN the Merchant returns to `/onboarding` with an incomplete OnboardingSession, THE Wizard SHALL restore the Merchant to the last completed step.
3. THE Wizard SHALL store all session state server-side on the OnboardingSession record rather than in browser storage.

### Requirement 11: Platform Adapter Extensibility

**User Story:** As a developer, I want the platform integration to follow an adapter pattern so that adding new platforms requires only a new adapter class.

#### Acceptance Criteria

1. THE StoreAdapter interface SHALL define methods for fetching store info, catalog data, and branding assets.
2. THE ShopifyAdapter SHALL implement the StoreAdapter interface by wrapping the existing ShopifyAdminService.
3. THE ManualAdapter SHALL implement the StoreAdapter interface by providing empty defaults for manual data entry.
4. WHEN a new platform adapter is added (e.g., WooCommerce), THE Wizard SHALL require only a new class implementing StoreAdapter with no modifications to existing adapter classes.

### Requirement 12: Database Schema

**User Story:** As a developer, I want dedicated tables for onboarding sessions, app configs, feature flags, and themes so that wizard and app data are properly structured.

#### Acceptance Criteria

1. THE system SHALL create an `onboarding_sessions` table with columns: id, tenant_id, platform, step, shopify_domain, imported_data (JSON), wizard_payload (JSON), ai_recommended (JSON), completed_at, and timestamps.
2. THE system SHALL create an `app_configs` table with columns: id, tenant_id (unique), app_name, app_icon_url, splash_url, primary_color, secondary_color, font_family, theme_id, home_layout (JSON), navigation (JSON), publish_status, bundle_id_ios, bundle_id_android, and timestamps.
3. THE system SHALL create a `feature_flags` table with columns: id, tenant_id, feature_key, enabled, config (JSON), and timestamps with a UNIQUE constraint on (tenant_id, feature_key).
4. THE system SHALL create an `app_themes` table with columns: id, name, default_config (JSON), and timestamps, seeded with 6 theme records.
5. THE system SHALL store Shopify credentials (shopify_shop_domain, shopify_admin_token, shopify_client_id, shopify_client_secret) on the existing Tenant model rather than in a separate table.
