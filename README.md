# E-Commerce Platform — Laravel + Nuxt

![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![Laravel](https://img.shields.io/badge/Laravel-11-red)
![Nuxt](https://img.shields.io/badge/Nuxt-3-green)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-blue)
![License](https://img.shields.io/badge/license-MIT-lightgrey)

A full-stack e-commerce platform with a Laravel 11 JSON API backend and a Nuxt 3 SSR frontend. Supports Google OAuth login, product catalog, cart, Xendit payment gateway (invoice + webhook), COD orders, multi-marketplace sync (Shopee, Lazada, TikTok), and a role-based admin panel.

---

## Technical Overview

| Layer | Technology |
|---|---|
| API framework | Laravel 11 (PHP 8.2) |
| Frontend | Nuxt 3 (Vue 3 + TypeScript) |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis 7 |
| Auth | Laravel Sanctum (token-based) + Google OAuth 2.0 |
| Payments | Xendit (hosted invoice) + COD |
| Styling | Tailwind CSS + @tailwindcss/typography |
| State management | Pinia |
| Infrastructure | Docker Compose (Postgres + Redis containers) |

---

## Why This Architecture?

### Thin controllers, fat services

Controllers do three things: validate the request, call a service, return a response. All business logic lives in `app/Services/`. This keeps controllers readable and makes the logic independently testable without booting the HTTP layer.

| Service | Responsibility |
|---|---|
| `ProductService` | Catalog queries, slug resolution, soft deletes |
| `CartService` | Guest + authenticated cart merge, item management |
| `PricingService` | Coupon validation, discount calculation, totals |
| `CheckoutService` | Stock reservation, order creation, Xendit invoice creation, COD flow |
| `OrderService` | Status transition enforcement (`VALID_TRANSITIONS` map), history recording |
| `Marketplace/*` | Shopee / Lazada / TikTok adapter stubs behind `MarketplaceInterface` |

### Constants as single source of truth

Every enum-like value (roles, order statuses, payment methods, coupon types) is defined as individual PHP constants on its model and exposed as a derived array:

```php
// One line to add a new role:
public const ROLE_SUPERVISOR = 'supervisor';
public const ROLES = [..., self::ROLE_SUPERVISOR];
```

The `GET /api/v1/config` endpoint exposes all constant arrays as JSON, so frontend dropdowns never hardcode values — they read from the API.

### Stock reservation pattern

When a customer checks out, `stock_quantity` is not decremented immediately. Instead `reserved_quantity` is incremented. Stock is only fully deducted when payment is confirmed (Xendit webhook or admin COD mark-paid). If payment expires, the reservation is released. This prevents overselling during payment processing.

### Order status machine

`OrderService::updateStatus()` enforces a strict transition map. You cannot ship an unpaid order or go backwards from delivered. Any attempt throws an exception before touching the database.

```
pending_payment → paid → processing → shipped → delivered
pending_payment → cancelled
paid            → cancelled
```

---

## Security

- **Authentication** — Laravel Sanctum bearer tokens. All non-public routes require `auth:sanctum` middleware.
- **Authorization** — Custom `RoleMiddleware` validates `User::ROLE_*` constants. Admin/manager routes are guarded at the route level, not inside controllers.
- **Google OAuth** — Server-side redirect flow only. No GSI popup (blocked by ad blockers). ID token verified with Google's tokeninfo endpoint.
- **Xendit webhooks** — Verified by comparing `X-CALLBACK-TOKEN` header against `XENDIT_WEBHOOK_TOKEN` env variable. Requests with mismatched tokens are rejected with 401.
- **Mass-assignment protection** — All models use explicit `$fillable` lists. No `$guarded = []` shortcuts.
- **CORS** — Configured via `config/cors.php` (published explicitly). Only `FRONTEND_URL` origin is allowed.
- **Input validation** — All controller inputs validated with Laravel's `$request->validate()` before reaching service layer.
- **Soft deletes** — Products are soft-deleted. Historical order items retain their snapshot of product name and price, unaffected by later edits or deletes.

---

## Performance

- **Database indexes** — Foreign keys, `slug` (products), `order_number`, `email` (users), `status` columns on orders and payments are all indexed.
- **Pagination** — All list endpoints paginate at 20 items. No unbounded queries.
- **SSR + SPA hybrid** — Nuxt `routeRules` renders product/catalog pages server-side for SEO. Account and admin pages are SPA (client-side only), avoiding unnecessary server renders for authenticated-only views.
- **Eager loading** — Services use `with(['items', 'user'])` to prevent N+1 queries on order and product listings.
- **JSONB columns** — `shipping_address`, `billing_address`, `credentials` (marketplace) stored as PostgreSQL `jsonb`, cast to arrays by Eloquent. No separate address-line join tables for snapshot data.

---

## Roles & Authorization

| Role | Access |
|---|---|
| `customer` | Browse products, manage own cart, checkout, view own orders |
| `warehouse` | View orders, update order status (processing → shipped) |
| `manager` | All warehouse access + manage products, view all orders |
| `admin` | Full access including user management, marketplace config, mark COD paid |

Roles are defined as constants on `User` and enforced via `role:admin,manager` middleware syntax on route groups.

---

## API Endpoints

**Base URL:** `http://localhost:8000/api/v1`

### Public

| Method | URL | Description |
|---|---|---|
| `GET` | `/config` | App enums (roles, statuses, payment methods, coupon types) |
| `GET` | `/auth/google` | Redirect to Google OAuth consent screen |
| `GET` | `/auth/google/callback` | OAuth callback — returns Sanctum token |
| `GET` | `/products` | Paginated product listing (filterable, sortable) |
| `GET` | `/products/{slug}` | Single product with variants and images |
| `GET` | `/categories` | Active categories ordered by position |
| `POST` | `/webhooks/xendit` | Xendit payment webhook (token-verified, no auth) |

### Authenticated (`auth:sanctum`)

| Method | URL | Description |
|---|---|---|
| `GET` | `/auth/me` | Current user profile |
| `POST` | `/auth/logout` | Revoke current token |
| `POST` | `/checkout` | Create order from cart (COD or Xendit) |
| `POST` | `/orders/{order}/mark-paid` | Mark COD order as paid (admin only) |

### Admin / Manager (`auth:sanctum` + `role:admin,manager`)

| Method | URL | Description |
|---|---|---|
| `POST` | `/products` | Create product |
| `PATCH` | `/products/{id}` | Update product |
| `DELETE` | `/products/{id}` | Soft-delete product |

### Query Parameters — `GET /products`

| Parameter | Type | Description |
|---|---|---|
| `search` | string | Full-text search on name and description |
| `category_id` | integer | Filter by category |
| `is_featured` | boolean | Featured products only |
| `sortBy` | string | Column to sort by (`name`, `price_cents`, `created_at`) |
| `sortOrder` | `asc` \| `desc` | Sort direction |
| `page` | integer | Page number (20 per page) |

---

## Data Models

| Model | Key fields |
|---|---|
| `User` | `role`, `google_id`, `is_active` |
| `Product` | `slug`, `is_active`, `is_featured`, soft deletes |
| `ProductVariant` | `sku`, `stock_quantity`, `reserved_quantity`, option columns |
| `Order` | `order_number`, `status`, `source`, `payment_method`, address snapshots |
| `OrderItem` | Snapshot of `product_name`, `variant_name`, `unit_price_cents` at time of order |
| `Payment` | `xendit_invoice_id`, `xendit_invoice_url`, `status`, `paid_at` |
| `Refund` | `xendit_refund_id`, `amount_cents`, `status` |
| `Coupon` | `code`, `type` (percentage/fixed), `discount_value`, usage limits |
| `MarketplaceConnection` | `platform`, `credentials` (jsonb), `is_active` |
| `MarketplaceSyncLog` | `direction`, `entity_type`, `status`, request/response bodies (jsonb) |

---

## Setup

### Prerequisites

- Docker Desktop
- PHP 8.2 + Composer
- Node.js 20+

### 1. Start infrastructure

```bash
docker-compose up -d
```

This starts PostgreSQL 16 on port 5432 and Redis 7 on port 6379.

### 2. API setup

```bash
cd api
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Edit `.env` with your values:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecommerce_dev
DB_USERNAME=ecommerce_user
DB_PASSWORD=ecommerce_pass

FRONTEND_URL=http://localhost:3000

GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/google/callback

XENDIT_SECRET_KEY=xnd_development_...
XENDIT_WEBHOOK_TOKEN=your-webhook-token
```

Start the API:

```bash
php artisan serve
# API available at http://localhost:8000
```

### 3. Frontend setup

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
# Frontend available at http://localhost:3000
```

`.env` for frontend:

```env
NUXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NUXT_PUBLIC_SITE_URL=http://localhost:3000
```

### 4. Seed admin user

The `AdminSeeder` creates `admin@example.com` with role `admin`. Update the email and password in `database/seeders/AdminSeeder.php` before seeding in production.

---

## Project Structure

```
ecommerce-platform-laravel-nuxt/
├── docker-compose.yml
├── api/                          # Laravel 11
│   ├── app/
│   │   ├── Contracts/
│   │   │   └── MarketplaceInterface.php
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── CheckoutController.php
│   │   │   │   ├── ConfigController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   └── XenditWebhookController.php
│   │   │   └── Middleware/
│   │   │       └── RoleMiddleware.php
│   │   ├── Models/               # 18 Eloquent models
│   │   └── Services/
│   │       ├── CartService.php
│   │       ├── CheckoutService.php
│   │       ├── OrderService.php
│   │       ├── PricingService.php
│   │       ├── ProductService.php
│   │       └── Marketplace/
│   │           ├── ShopeeService.php
│   │           ├── LazadaService.php
│   │           └── TikTokService.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
│       └── api.php
└── frontend/                     # Nuxt 3
    ├── composables/
    │   ├── useAuth.ts
    │   ├── useCart.ts
    │   └── useConfig.ts
    ├── constants/
    │   └── enums.ts
    ├── stores/
    │   └── auth.ts
    └── pages/
```

---

## License

MIT License — 2026 Paulino Awino
