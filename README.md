# E-Commerce API: Inventory Hold & Order System

## Overview
This Laravel API implements a core e-commerce backend for product inventory management, temporary holds, order creation, and payment webhooks. It supports finite stock, prevents overselling under concurrency, and handles asynchronous events. Built with Laravel 12+, MySQL, and Eloquent. Key features: Atomic stock updates, 2-min hold expiry via scheduler, idempotent webhooks, and cached reads.

## Assumptions and Invariants Enforced
### Assumptions
- **Database**: MySQL (InnoDB engine) with REPEATABLE READ isolation for locking.
- **Environment**: Single-item holds/orders; no user auth (stateless). Holds expire in ~2 minutes (configurable via `config/constants.php`).
- **External Dependencies**: Payment provider sends webhooks to `/api/payments/webhook` with `idempotency_key`, `order_id`, `status` (success/failure). No real payment integration.

- **Testing**: Uses real dev DB (via .env.testing); no in-memory SQLite to simulate production.

### Invariants Enforced
- **No Overselling**: `lockForUpdate()` + transactions ensure stock checks/decrements are atomic. Parallel holds on boundary stock (e.g., qty=1 when stock=1) result in one success, one failure.
- **Hold Single-Use**: Orders validate active/unexpired holds; mark as 'used' post-creation.
- **Expiry Cleanup**: Scheduler (`app:expire-holds-command`) runs every minute, bulk-updates expired holds to 'expired', releases stock via grouped increments (no N+1).
- **Webhook Safety**: Idempotent (unique key check in `idempotency_logs`); out-of-order ignores non-pending orders. Failure releases stock via denormalized qty in orders.
- **Cache Correctness**: Product reads cached (TTL configurable via `PRODUCT_CACHE_TTL=60` in .env); invalidated on stock saves (explicit `$product->save()` after decrement).
- **Data Integrity**: FK cascades (e.g., delete hold → delete order); enums for status (holds: active/used/expired; orders: pending/paid/cancelled).

## Database Schema and Model Relations
**Product**
- id (bigint, PK, auto-increment)
- code (varchar 100, unique)
- name (varchar 100)
- price (decimal 10,2)
- available_stock (integer, default 0)
- total_stock (integer, default 0)
- created_at, updated_at (timestamps)
- *hasMany* Holds: $this->hasMany(Hold::class); (one product → many holds).


**Hold**
- id (bigint, PK, auto-increment)
- product_id (bigint, FK to products.id, cascade delete)
- quantity (integer)
- expires_at (timestamp)
- status (enum: 'active', 'used', 'expired', default 'active')
- created_at, updated_at (timestamps)
- *belongsTo* Product: $this->belongsTo(Product::class); (many holds → one product).

**Order**
- id (bigint, PK, auto-increment)
- product_id (bigint, FK to product.id, cascade delete)
- hold_id (bigint, FK to holds.id, cascade delete)
- quantity (integer, denormalized)
- status (enum: 'pending', 'paid', 'cancelled', default 'pending')
- created_at, updated_at (timestamps)
- *belongsTo* Hold: $this->belongsTo(Hold::class); (many orders → one hold).
- *belongsTo* Product: $this->belongsTo(Hold::class); (many orders → one hold).

**idempotency_logs**
- id (bigint, PK, auto-increment)
- key (string, unique)
- created_at, updated_at (timestamps)

## How to Run the App and Tests
### Prerequisites
- PHP 8.2+, Composer, MySQL 8+.
- Clone repo: `git clone <repo> && cd flash-sale-task`.
- Install deps: `composer install`.

### App Setup
1. **Copy Env**: `cp .env.example .env`.

2. **Configure .env**:
   - DB: `DB_CONNECTION=mysql`, `DB_DATABASE=your_db`, etc.
   - Cache: `CACHE_DRIVER=database`.
   - Custom: `PRODUCT_CACHE_TTL=60`, `IDEMPOTENCY_CLEANUP_DAYS=2`, `IDEMPOTENCY_CLEANUP_CRON="0 0 * * *"`

3. **Migrate & Seed**:
    - `php artisan key:generate`
    - `php artisan migrate`
    - `php artisan db:seed --class=ProductSeeder`

4. **Scheduler: Run expiry cleanup**:
   - `php artisan schedule:work`   

### Testing Setup

1. **Copy Env**: cp .env .env.testing.
2. **Configure .env.testing**:
3. **Migrate & Seed**:
   - `php artisan key:generate --env=testing`
   - `php artisan migrate --env=testing`
   - `php artisan db:seed --env=testing`

4. **Run Tests**:
    - `php artisan test --env=testing`.
