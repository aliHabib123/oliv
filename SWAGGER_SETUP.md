# Swagger API Documentation Setup

## Installation

Add to `composer.json`:
```json
"require-dev": {
    "darkaonline/l5-swagger": "^8.6"
}
```

Run:
```bash
composer install
php artisan l5-swagger:generate
```

## Access Documentation

Visit: `http://your-domain/api/documentation`

## Generate/Update Docs

```bash
php artisan l5-swagger:generate
```

## Authentication

All endpoints require `X-App-Key` header with tenant app key.
