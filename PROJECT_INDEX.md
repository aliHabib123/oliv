# Oliv-Tech Project Index

## Overview

Oliv-Tech is a Laravel-based SaaS platform that enables businesses to create and manage mobile e-commerce applications. The platform integrates with Shopify for product and order management, supports multi-tenancy, and provides a comprehensive admin interface using Filament.

**Key Features:**
- Multi-tenant architecture
- Shopify storefront integration
- Subscription-based addon system
- Mobile app API backend
- Admin panel with Filament
- Cart and checkout functionality
- Push notifications via OneSignal
- Payment processing with Stripe

## Architecture

### Technology Stack
- **Framework:** Laravel 12
- **PHP Version:** 8.2+
- **Database:** MySQL/PostgreSQL (via Eloquent ORM)
- **Admin Panel:** Filament 4.4
- **Permissions:** Spatie Laravel Permission
- **Payments:** Stripe PHP SDK
- **Frontend Build:** Vite
- **Testing:** PHPUnit

### Project Structure

```
├── app/
│   ├── Console/Commands/
│   ├── Filament/ (Admin panel resources)
│   ├── Http/Controllers/
│   │   ├── Api/ (Mobile app API endpoints)
│   │   └── SubscriptionController.php
│   ├── Models/ (Eloquent models)
│   ├── Providers/
│   └── Services/ (Business logic services)
├── config/ (Laravel configuration)
├── database/
│   ├── migrations/ (Database schema)
│   └── seeders/
├── public/ (Web assets)
├── resources/ (Views, CSS, JS)
├── routes/
│   ├── api.php (API routes)
│   └── web.php (Web routes)
└── tests/ (Test suites)
```

## Models

### Core Models

| Model | Description | Key Relationships |
|-------|-------------|-------------------|
| **Tenant** | Represents a client/store | users, homeLayouts, tenantAddons |
| **User** | System users with roles | tenant (belongsTo) |
| **Package** | Subscription packages | subscriptions |
| **Subscription** | Tenant subscriptions | tenant, package |
| **Addon** | Available addons | tenantAddons |
| **TenantAddon** | Tenant addon subscriptions | tenant, addon |

### E-commerce Models

| Model | Description | Key Relationships |
|-------|-------------|-------------------|
| **Cart** | Shopping cart | tenant, cartEvents |
| **CartEvent** | Cart activity log | cart |
| **CheckoutEvent** | Checkout activity log | - |
| **DeviceToken** | Push notification tokens | tenant |

### Content Models

| Model | Description | Key Relationships |
|-------|-------------|-------------------|
| **HomeLayout** | Home page configuration | tenant |
| **AppConfiguration** | App settings and branding | tenant |
| **Template** | UI templates | - |

## Controllers

### API Controllers (`app/Http/Controllers/Api/`)

| Controller | Endpoints | Description |
|------------|-----------|-------------|
| **ContentController** | `/v1/home-layout`, `/v1/introduction`, `/v1/app-config`, `/v1/payment-settings` | Static content and configuration |
| **ProductController** | `/v1/products/{id}/default-variant` | Product data helpers |
| **DeviceTokenController** | `/v1/device-tokens/*` | Push notification management |
| **AddonController** | `/v1/addons` | Available addons list |
| **CartController** | `/v1/cart/*` | Shopping cart operations |
| **CheckoutController** | `/v1/checkout/create` | Checkout processing |
| **ShopifyWebhookController** | `/webhooks/shopify/*` | Shopify webhook handlers |

### Web Controllers

| Controller | Routes | Description |
|------------|--------|-------------|
| **SubscriptionController** | - | Subscription management |
| **TenantAddonController** | - | Addon management |

## API Routes

### Content & Configuration
- `GET /v1/ping` - Health check
- `GET /v1/home-layout` - Home page layout
- `GET /v1/introduction` - Onboarding content
- `GET /v1/app-config` - App configuration
- `GET /v1/payment-settings` - Shopify payment settings

### Products
- `GET /v1/products/{id}/default-variant` - Default product variant

### Device Management
- `POST /v1/device-tokens/register` - Register device for notifications
- `POST /v1/device-tokens/unregister` - Unregister device

### Addons
- `GET /v1/addons` - List subscribed addons

### Cart Management
- `POST /v1/cart/add` - Add item to cart
- `POST /v1/cart/remove` - Remove item from cart
- `POST /v1/cart/update` - Update cart item
- `GET /v1/cart` - Get cart contents

### Checkout
- `POST /v1/checkout/create` - Create checkout

### Webhooks
- `POST /webhooks/shopify/checkouts/create` - Shopify checkout created
- `POST /webhooks/shopify/checkouts/update` - Shopify checkout updated
- `POST /webhooks/shopify/orders/create` - Shopify order created

## Services

### Business Logic Services (`app/Services/`)

| Service | Description |
|---------|-------------|
| **CategorySyncService** | Sync categories from Shopify |
| **Cart/** | Cart-related services |
| **OneSignal/** | Push notification services |
| **Shopify/** | Shopify integration services |

## Database Migrations

### Key Migrations
- `create_users_table` - User accounts
- `create_permission_tables` - Spatie permissions
- `create_tenants_table` - Multi-tenant setup
- `create_app_configurations_table` - App settings
- `create_cart_events_table` - Cart activity tracking
- `create_carts_table` - Shopping carts
- `create_checkout_events_table` - Checkout tracking
- `create_packages_table` - Subscription packages
- `create_addons_table` - Available addons
- `create_subscriptions_table` - Tenant subscriptions
- `create_tenant_addons_table` - Addon subscriptions
- `create_device_tokens_table` - Push notification tokens

## Configuration

### Key Config Files
- `app.php` - Application settings
- `auth.php` - Authentication configuration
- `database.php` - Database connections
- `services.php` - Third-party service credentials
- `permission.php` - Permission settings

## Filament Admin Panel

### Resources (`app/Filament/Resources/`)
- Tenant management
- User management
- Package management
- Addon management
- Subscription management

### Pages (`app/Filament/Pages/`)
- Dashboard
- Analytics

### Widgets (`app/Filament/Widgets/`)
- Statistics widgets

## Testing

### Test Structure
```
tests/
├── Feature/ (Feature tests)
└── Unit/ (Unit tests)
```

## Development Commands

### Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Development
```bash
composer run dev  # Runs server, queue, logs, and Vite concurrently
```

### Testing
```bash
php artisan test
```

## Deployment

- Uses Laravel's standard deployment practices
- Requires database migration on deploy
- Queue workers for background processing
- Vite for asset compilation

## Security

- Multi-tenant data isolation
- API key authentication (`X-App-Key` header)
- Optional Shopify customer token authentication
- Device ID tracking for notifications
- Spatie permission system for role-based access

## Integrations

- **Shopify:** Product catalog, orders, webhooks
- **Stripe:** Payment processing
- **OneSignal:** Push notifications
- **Filament:** Admin interface

## API Documentation

Detailed API documentation is available in `API_DOCUMENTATION.md` with request/response examples and authentication details.

---

*This index was generated on March 5, 2026. For the latest information, refer to the codebase and API documentation.*