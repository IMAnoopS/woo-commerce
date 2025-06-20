// README.md

# WooCommerce Product Sync System

## Tech Stack
- Backend: Laravel 10+
- Database: PostgreSQL or MySQL
- Auth: JWT or Sanctum
- WooCommerce API: [WooCommerce REST API Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/#products)

## Features
- User Register/Login using Sanctum
- Create, Update, Delete Products
- Sync to WooCommerce via REST API

## WooCommerce Integration
- Sync product to WooCommerce upon creation/update
- Delete from WooCommerce on deletion
- Endpoint: /wp-json/wc/v3/products
- Auth via Basic Auth (Consumer Key & Secret)
- Graceful error handling + logging
- Product statuses: Created, Synced, Failed

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Test User
- Email: seller@example.com
- Password: password
