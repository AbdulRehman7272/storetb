# TBrand Store

Production-oriented Laravel 12 e-commerce application for TBrand. The code is reusable for separate client installations with their own database, domain, branding, catalog, customers, and orders.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL for production
- Blade storefront with Vite assets
- Bootstrap-based admin theme assets from `theme/`
- Database queue driver by default
- ApexCharts for reports

## Quick Install

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
```

For this local build, the seeded admin account is:

```text
URL: /admin/login
Email: admin@tbrand.pk
Password: password
```

Change the password before production.

## Database

Set these in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tbrand_store
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

Run a fresh development install with demo data:

```bash
php artisan migrate:fresh --seed
```

## Queue And Scheduler

The app is configured for the database queue. Start a worker:

```bash
php artisan queue:work --tries=3
```

Add the scheduler to cron or Windows Task Scheduler:

```bash
php artisan schedule:run
```

## Storage

Public product uploads use `public/storage` through Laravel's storage link.

Manual payment proof uploads are stored privately on the local disk under `storage/app/private` or `storage/app/payment-proofs` depending on disk config and are served only through authenticated admin routes.

```bash
php artisan storage:link
```

## Production Deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Recommended folder permissions:

```text
storage/ writable by the web user
bootstrap/cache/ writable by the web user
public/storage symlink present
```

Recommended PHP extensions:

```text
bcmath, ctype, curl, dom, fileinfo, gd or imagick, json, mbstring, openssl, pdo_mysql, tokenizer, xml, zip
```

## Implemented Modules

- Storefront home, dynamic root category pages, search, product page, cart, checkout, order success, order tracking, custom pages.
- Admin login, dashboard, products, variants through bulk colour/size generation, categories, orders, payment accounts, customers, inventory, reports, pages, settings, staff/roles overview.
- Product quick-add requires only name and price.
- Default product image fallback is configurable in settings.
- COD is the default checkout payment method.
- Manual payment account selection, transaction reference, private screenshot upload, and admin proof access.
- Separate order status and payment status fields.
- Stock deduction and inventory movement logging during checkout.
- TBrand logo and favicon assets integrated from `public/brand/tbrand`.

## Tests

```bash
php artisan test
```

Current feature coverage verifies storefront/category rendering, quick product publishing with only name and price, COD guest checkout, and private manual-payment proof upload.

## Backups

Back up:

- MySQL database
- `.env`
- `storage/app`
- `public/storage`
- uploaded brand/product media

Use database dumps before every production update:

```bash
mysqldump -u USER -p DB_NAME > backup.sql
```

## Update Guidance

1. Back up database and uploads.
2. Pull or copy the new source.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Run `npm ci && npm run build`.
5. Run `php artisan migrate --force`.
6. Clear and rebuild caches.
7. Restart queue workers.

## Known First-Version Limits

This first build provides the functional foundation and key commerce workflows. Advanced drag-and-drop homepage section ordering, PDF invoice rendering, courier integrations, Excel exports, image WebP queue processing, and full granular UI for every permission can be extended on top of the existing schema and admin shell.
