# Requirements Document

## Introduction

This document defines the requirements for a native mobile application (iOS/Android) that integrates with an existing Laravel backend to provide a complete shopping experience. The app covers product browsing via Shopify Storefront API, cart management, two checkout flows (Shopify-hosted and native with BNPL support via Tabby/Tamara), push notifications, and order tracking. The mobile app is a client that consumes the backend's REST API — no backend changes are in scope.

## Glossary

- **Mobile_App**: The native iOS/Android mobile application being built
- **Backend_API**: The existing Laravel REST API at the base URL, authenticated via X-App-Key header
- **Storefront_API**: Shopify's Storefront GraphQL API used for product browsing, collections, and search
- **Cart_Service**: The backend cart management endpoints (add, remove, update, get) at /v1/cart/*
- **Checkout_Session**: A server-side session object representing a native checkout attempt, with states: pending → payment_initiated → awaiting_confirmation → authorized → captured → order_created (terminal), plus failed/expired terminal states
- **Shopify_Checkout**: The Shopify-hosted checkout flow initiated via POST /v1/checkout/create, rendered in a WebView
- **Native_Checkout**: The in-app checkout flow using POST /v1/checkout/session → PSP initialize → POST /v1/checkout/confirm
- **PSP**: Payment Service Provider — Tabby or Tamara for BNPL, plus Stripe, PayPal, Telr
- **BNPL**: Buy Now Pay Later — installment payment offered by Tabby and Tamara
- **Tabby_Gateway**: Tabby BNPL payment provider, initialized via POST /v1/payments/tabby/initialize
- **Tamara_Gateway**: Tamara BNPL payment provider, initialized via POST /v1/payments/tamara/initialize
- **App_Config**: Tenant configuration fetched from GET /v1/app-config containing feature flags, branding, and settings
- **Device_ID**: A unique UUID generated and persisted on the device, sent as X-Device-ID header for guest cart tracking
- **Customer_Token**: A Shopify customer access token sent as X-Shopify-Customer-Token header for authenticated users
- **Idempotency_Key**: A client-generated UUID sent as Idempotency-Key header when creating checkout sessions to prevent duplicates
- **Push_Token**: A device push notification token from OneSignal or Firebase, registered via POST /v1/device-tokens/register

## Requirements

### Requirement 1: App Initialization and Configuration

**User Story:** As a user, I want the app to load its configuration on launch, so that I see the correct branding, features, and content.

#### Acceptance Criteria

1. WHEN the Mobile_App launches, THE Mobile_App SHALL fetch App_Config from GET /v1/app-config and cache the response locally
2. WHEN the Mobile_App launches, THE Mobile_App SHALL fetch the home layout from GET /v1/home-layout and render the home screen accordingly
3. WHEN the Mobile_App launches for the first time, THE Mobile_App SHALL generate a persistent Device_ID (UUID v4) and store it in secure local storage
4. WHEN the Mobile_App launches for the first time, THE Mobile_App SHALL fetch and display the introduction content from GET /v1/introduction
5. WHEN the App_Config response contains ecommerce.tabby_enabled set to false, THE Mobile_App SHALL hide all Tabby payment options from the checkout flow
6. WHEN the App_Config response contains ecommerce.tamara_enabled set to false, THE Mobile_App SHALL hide all Tamara payment options from the checkout flow
7. IF the GET /v1/app-config request fails, THEN THE Mobile_App SHALL display a retry prompt with an option to retry the request
8. THE Mobile_App SHALL include the X-App-Key header in every request to the Backend_API

### Requirement 2: Product Browsing

**User Story:** As a shopper, I want to browse products, collections, and search for items, so that I can find what I want to buy.

#### Acceptance Criteria

1. THE Mobile_App SHALL fetch and display product listings from the Storefront_API using GraphQL queries
2. WHEN a user taps a product, THE Mobile_App SHALL display the product detail screen with title, description, images, price, compare-at price, and available variants
3. WHEN a product has options with values other than "Default Title", THE Mobile_App SHALL display variant selection controls (size, color, etc.)
4. WHEN a product has a single option named "Title" with value "Default Title", THE Mobile_App SHALL treat the product as a simple product and hide variant selection
5. THE Mobile_App SHALL support browsing products by collection using Storefront_API collection queries
6. WHEN a user enters a search query, THE Mobile_App SHALL query the Storefront_API product search and display matching results
7. WHEN a product variant has a compareAtPriceV2 value, THE Mobile_App SHALL display both the current price and the original price with a visual sale indicator

### Requirement 3: Cart Management

**User Story:** As a shopper, I want to manage items in my cart, so that I can prepare my order before checkout.

#### Acceptance Criteria

1. WHEN a user adds a product to the cart, THE Mobile_App SHALL send a POST request to /v1/cart/add with the variant_id, quantity, and product_id
2. WHEN a user changes the quantity of a cart item, THE Mobile_App SHALL send a POST request to /v1/cart/update with the line_id and new quantity
3. WHEN a user removes an item from the cart, THE Mobile_App SHALL send a POST request to /v1/cart/remove with the line_id
4. WHEN the Mobile_App displays the cart screen, THE Mobile_App SHALL fetch the current cart state from GET /v1/cart
5. THE Mobile_App SHALL display each cart line item with its variant image, product title, selected options, unit price, quantity, and line total
6. THE Mobile_App SHALL display the cart's estimatedCost.totalAmount as the cart subtotal
7. WHEN a user is logged in, THE Mobile_App SHALL include both the X-Shopify-Customer-Token and X-Device-ID headers in all cart requests
8. WHEN a user is not logged in, THE Mobile_App SHALL include only the X-Device-ID header in all cart requests
9. WHEN the GET /v1/cart response returns cart as null, THE Mobile_App SHALL display an empty cart state with a prompt to browse products
10. IF a cart operation returns ok:false, THEN THE Mobile_App SHALL display the error message from the response to the user

### Requirement 4: Shopify-Hosted Checkout Flow

**User Story:** As a shopper, I want to complete my purchase through the standard Shopify checkout, so that I can use Shopify's payment methods and apply discount codes.

#### Acceptance Criteria

1. WHEN a user initiates the Shopify checkout, THE Mobile_App SHALL send a POST request to /v1/checkout/create with the current cart line_items (variant_id and quantity for each item)
2. WHEN the /v1/checkout/create response returns ok:true, THE Mobile_App SHALL open the checkout_url in an in-app WebView
3. WHILE the Shopify_Checkout WebView is open, THE Mobile_App SHALL monitor the WebView URL for order confirmation or completion indicators
4. WHEN the user completes the Shopify checkout, THE Mobile_App SHALL close the WebView and display an order confirmation screen
5. WHEN the user dismisses the WebView without completing checkout, THE Mobile_App SHALL return the user to the cart screen
6. IF the /v1/checkout/create request fails, THEN THE Mobile_App SHALL display the error message and allow the user to retry

### Requirement 5: Native Checkout Flow with BNPL

**User Story:** As a shopper, I want to pay using Buy Now Pay Later options (Tabby or Tamara) through a native in-app checkout, so that I can split my payment into installments.

#### Acceptance Criteria

1. WHEN a user initiates native checkout, THE Mobile_App SHALL collect the customer email, phone, and shipping address before proceeding
2. WHEN the user submits checkout details, THE Mobile_App SHALL generate an Idempotency_Key (UUID v4) and send a POST request to /v1/checkout/session with line_items, customer, shipping_address, amount, and currency
3. WHEN the /v1/checkout/session response returns ok:true, THE Mobile_App SHALL store the session_id and present the available BNPL payment options
4. WHEN the user selects Tabby as the payment method, THE Mobile_App SHALL send a POST request to /v1/payments/tabby/initialize with the session_id
5. WHEN the Tabby_Gateway initialization returns ok:true, THE Mobile_App SHALL open the web_url in an in-app WebView for the user to complete the Tabby payment flow
6. WHEN the user selects Tamara as the payment method, THE Mobile_App SHALL send a POST request to /v1/payments/tamara/initialize with the session_id
7. WHEN the Tamara_Gateway initialization returns ok:true, THE Mobile_App SHALL open the checkout_url in an in-app WebView for the user to complete the Tamara payment flow
8. WHEN the user returns from the PSP WebView after completing payment, THE Mobile_App SHALL send a POST request to /v1/checkout/confirm with the session_id
9. WHEN the /v1/checkout/confirm response returns status "order_created", THE Mobile_App SHALL display the order confirmation with the shopify_order_name
10. WHEN the /v1/checkout/confirm response returns status "processing" (HTTP 202), THE Mobile_App SHALL display a "processing" state and poll the confirm endpoint at 5-second intervals up to 6 times
11. IF the /v1/checkout/confirm response returns HTTP 402 (payment not authorized), THEN THE Mobile_App SHALL inform the user that payment was not completed and offer to retry or choose a different payment method
12. IF the PSP initialization returns ok:false with a fallback_checkout_url, THEN THE Mobile_App SHALL offer the user the option to complete checkout via the fallback Shopify checkout URL
13. IF the Checkout_Session has been in payment_initiated state for more than 30 minutes, THEN THE Mobile_App SHALL treat the session as expired and prompt the user to start a new checkout

### Requirement 6: Checkout Session State Tracking

**User Story:** As a shopper, I want the app to accurately reflect my checkout progress, so that I know the status of my order at all times.

#### Acceptance Criteria

1. THE Mobile_App SHALL track the Checkout_Session status locally as one of: pending, payment_initiated, awaiting_confirmation, authorized, captured, order_created, failed, or expired
2. WHEN the Checkout_Session status is "pending", THE Mobile_App SHALL allow the user to select a payment method and initialize payment
3. WHEN the Checkout_Session status is "payment_initiated", THE Mobile_App SHALL display a "payment in progress" indicator and prevent initiating a second payment
4. WHEN the Checkout_Session status is "failed", THE Mobile_App SHALL display the failure reason and offer to start a new checkout session
5. WHEN the Checkout_Session status is "expired", THE Mobile_App SHALL inform the user that the session has expired and offer to create a new session
6. WHEN the /v1/checkout/confirm endpoint is called and the session is already in "order_created" state, THE Mobile_App SHALL display the existing order details from the idempotent response without creating a duplicate

### Requirement 7: Authentication and User Identity

**User Story:** As a returning customer, I want to log in so that my cart and checkout are associated with my account.

#### Acceptance Criteria

1. WHEN a user logs in with Shopify customer credentials, THE Mobile_App SHALL obtain and securely store the Customer_Token
2. WHILE a user is logged in, THE Mobile_App SHALL include the X-Shopify-Customer-Token header in all Backend_API requests that support it
3. WHEN a user logs out, THE Mobile_App SHALL remove the stored Customer_Token and continue operating in guest mode using Device_ID only
4. THE Mobile_App SHALL persist the Device_ID across app sessions regardless of login state
5. WHEN a user logs out, THE Mobile_App SHALL call POST /v1/device-tokens/unregister with the current Push_Token

### Requirement 8: Push Notifications

**User Story:** As a user, I want to receive push notifications, so that I stay informed about promotions and order updates.

#### Acceptance Criteria

1. WHEN the Mobile_App obtains a Push_Token from OneSignal or Firebase, THE Mobile_App SHALL send a POST request to /v1/device-tokens/register with the push_token, provider, platform, device_type, and app_version
2. WHEN the Push_Token changes (token refresh), THE Mobile_App SHALL re-register the new token via POST /v1/device-tokens/register
3. WHEN a user logs in, THE Mobile_App SHALL re-register the Push_Token with the user_id included in the request body
4. WHEN a user logs out, THE Mobile_App SHALL call POST /v1/device-tokens/unregister with the current Push_Token
5. WHEN a push notification is received while the Mobile_App is in the foreground, THE Mobile_App SHALL display an in-app notification banner
6. WHEN a user taps a push notification, THE Mobile_App SHALL navigate to the relevant screen based on the notification payload

### Requirement 9: Payment Method Display and Feature Flags

**User Story:** As a shopper, I want to see only the payment methods available to me, so that I can choose a supported way to pay.

#### Acceptance Criteria

1. WHEN the Mobile_App displays payment options during native checkout, THE Mobile_App SHALL fetch payment settings from GET /v1/payment-settings
2. WHEN App_Config ecommerce.tabby_enabled is true, THE Mobile_App SHALL display the Tabby BNPL option with the text from ecommerce.tabby_text
3. WHEN App_Config ecommerce.tamara_enabled is true, THE Mobile_App SHALL display the Tamara BNPL option with the text from ecommerce.tamara_text
4. THE Mobile_App SHALL display accepted card brands from the payment_settings.acceptedCardBrands array on the checkout screen
5. THE Mobile_App SHALL fetch the tenant addons from GET /v1/addons and enable features based on addon keys (e.g., show WhatsApp chat button when "whatsapp_integration" addon is active)

### Requirement 10: Network Error Handling and Resilience

**User Story:** As a user, I want the app to handle network issues gracefully, so that I do not lose my progress or see confusing errors.

#### Acceptance Criteria

1. IF a Backend_API request fails due to a network timeout or connectivity loss, THEN THE Mobile_App SHALL display a user-friendly error message with a retry option
2. IF a Backend_API request returns HTTP 500, THEN THE Mobile_App SHALL display a generic error message and log the error details locally for debugging
3. WHEN creating a Checkout_Session, THE Mobile_App SHALL reuse the same Idempotency_Key on retry to prevent duplicate session creation
4. WHEN confirming a checkout, THE Mobile_App SHALL safely retry the /v1/checkout/confirm request because the endpoint is idempotent
5. IF a PSP initialization request returns HTTP 502, THEN THE Mobile_App SHALL display the fallback_checkout_url option if present in the response, or a retry option otherwise
6. THE Mobile_App SHALL implement exponential backoff with a maximum of 3 retries for transient Backend_API failures (HTTP 500, 502, 503)

### Requirement 11: Offline and Cached Content

**User Story:** As a user, I want to see cached content when offline, so that I can still browse previously loaded data.

#### Acceptance Criteria

1. THE Mobile_App SHALL cache the App_Config, home layout, and introduction content locally after each successful fetch
2. WHILE the device has no network connectivity, THE Mobile_App SHALL display cached content for previously loaded screens
3. WHILE the device has no network connectivity, THE Mobile_App SHALL disable cart and checkout actions and display an offline indicator
4. WHEN network connectivity is restored, THE Mobile_App SHALL refresh stale cached data and re-enable all actions

### Requirement 12: Deep Linking and Navigation

**User Story:** As a user, I want to open specific products or screens from external links, so that I can navigate directly to shared content.

#### Acceptance Criteria

1. WHEN the Mobile_App receives a deep link containing a product ID, THE Mobile_App SHALL navigate to the corresponding product detail screen
2. WHEN the Mobile_App receives a deep link containing a collection ID, THE Mobile_App SHALL navigate to the corresponding collection screen
3. IF a deep link references a resource that cannot be found, THEN THE Mobile_App SHALL display a "not found" message and navigate to the home screen
