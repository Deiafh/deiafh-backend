# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

A Laravel 12 JSON API backend for a restaurant ordering/delivery platform (Arabic-facing — many user-visible strings, e.g. discount validation messages, are in Arabic). It is **API-only**: `resources/` still contains the framework's default welcome page and `routes/web.php` only serves it — the real client app talks exclusively to `routes/api.php`.

## Commands

### Setup / running
- `composer install`, `npm install`
- `php artisan migrate` (SQLite by default, see `.env` `DB_CONNECTION`)
- `php artisan serve` — API server
- `composer run dev` — runs `serve` + `queue:listen` + `pail` (log viewer) + `vite` concurrently (this is the normal local dev command)
- `npm run dev` / `npm run build` — Vite/Tailwind v4 asset pipeline (only matters for the default welcome page; not used by the API)

### Tests
- `composer test` (clears config cache, then `php artisan test`) or just `php artisan test`
- Single test: `php artisan test --filter=test_method_name` or `php artisan test tests/Feature/SomeTest.php`
- Test env uses SQLite `:memory:`, array cache/session, sync queue (see `phpunit.xml`); no `.env` needed for tests

### Code style
- `vendor/bin/pint` — Laravel Pint formatter (no custom config, uses Laravel preset)

## Architecture

### Front vs Dashboard split
All real routes are registered in `routes/api.php` under two controller namespaces:
- `App\Http\Controllers\Front\*` — public, unauthenticated customer endpoints: browsing branches/menu/categories, validating carts, checking discount codes, placing orders, tracking an order by `order_reference`.
- `App\Http\Controllers\Dashboard\*` — admin endpoints, grouped under the `dashboard` prefix. Everything except `/dashboard/auth/login` sits behind `auth:api` (JWT guard) and Spatie permission roles.

### Auth & authorization
- JWT via `tymon/jwt-auth` on guard `api` (`config/auth.php`). `AuthController` issues/refreshes/revokes tokens and bumps `jwt.ttl` to 30 days at runtime when `remember` is set on login.
- Roles/permissions via `spatie/laravel-permission` (`HasRoles` on `User`). `RoleController`/`UserController` manage assignment; `UserController` also resizes uploaded avatars to webp (450px wide, quality 75) with `intervention/image` and stores them under `storage/users/`.

### Polymorphic, branch-aware pricing
`Price` is a single polymorphic table (`morphTo('entity')`) shared by `Item`, `ItemSize`, and `ItemOptionValue`. The morph map (`item`/`size`/`option_value` ↔ `PricingEntityType` enum) is registered in `AppServiceProvider::boot()`. Each priceable model exposes a `priceForBranch` relation that prefers a branch-specific row over the branch-`null` default price:
```php
->morphOne(Price::class, 'entity')
    ->orderByRaw("branch_id IS NULL ASC")->orderByDesc('branch_id')
    ->withDefault(['price' => 0])
```
When adding a new priceable entity, register it in both the morph map and `PricingEntityType`, and reuse this relation shape (see `Item::priceForBranch`).

### Working-period ("is the branch open") logic
`WorkingPeriodsService::getCurrent()` encodes "now" as a sortable string `<day-index 0–6, Saturday=0><His>` (e.g. Tuesday 14:30:00 → `"3143000"`). `WorkingPeriod` rows store `from_date`/`to_date` in this same encoded format, scoped to a `WorkingPeriodGroup`. A branch is "open" if either:
- it belongs to a `WorkingPeriodGroup` that has a matching period row, or
- it has no group at all and a *general* period (`working_period_group_id = null`) currently matches.

Both directions of the date comparison are needed to support overnight ranges (`from_date > to_date` wraps past midnight) — see the duplicated query shape in `Branch::isWorkingNow()`, `WorkingPeriodsService::isAvailableGeneralWorkingPeriod()`, and `BranchesService::getAllWorkingBranches()`; keep them in sync if this logic changes.

A refactor from a flat per-branch `branch_working_periods` table to the shared `WorkingPeriodGroup` model is **in progress** — see the four pending migrations dated `2026_06_04_*` and the working-tree diff touching `Branch`, `WorkingPeriod*`, `BranchesService`, `WorkingPeriodsService`.

### Cart → discount → order pipeline
`Front\OrderController` orchestrates three services, in this order (`validateCart` → `validateUserInfo`/`getPublicDiscounts`/`checkDiscountCode` → `getFinalInfo` → `placeOrder`):
- **`CartService`** — validates the cart payload shape (item/size/option/value existence, min/max option-value selection counts) and computes prices (`calculateItemPrice`, `getTotalCartPrice`, `getApplicableCartPrice` for the discount-eligible subtotal).
- **`DiscountService`** — static methods that validate a discount code against branch/location/payment-method/approach/date-range/usage-limit/min-order rules (`validateDiscountCode`), decide whether a discount applies to a given item via its include/exclude item & category lists (`isItemAppliable`), and compute cart/delivery discount amounts. User-facing error messages are Arabic strings.
- **`OrderService`** — validates `userInfo` (name, Egyptian phone prefixes `010/011/012/015`, delivery address/location), `validateOrder` ties cart + user info + branch (active & currently open) + settings + discount together, and `saveCashOrder` persists the `Order` together with its `OrderCart`/`OrderCartOption`/`OrderCartOptionValue` line items, snapshotting names and prices at order time so historical orders aren't affected by later catalog changes.

Expected request shapes (used across `validateCart`, `getFinalInfo`, `placeOrder`, discount endpoints):
- `cart`: `{ branchId, items: [{ id, count, size_id?, options?: [{ id, values: [{ id, count }] }], notes? }] }`
- `userInfo`: `{ name, phone, additional_phone, order_type: "delivery"|"pickup", location?, address?, notes?, payment_type: "cash"|"visa", approach? }`
- The currently selected branch is also passed via the `branchId` request **header** on several `Front` endpoints (e.g. `CategoryController`, `MenuController`, `OrderController::validateUserInfo`) — don't assume it's only ever in the body.

### Online payments
`App\OnlinePayment\PaymentProviders` is a string-backed enum listing supported gateways (`Paymob`, `Qnb`). Its case *values* are arrays (e.g. `['paymob']`), so use the static `::values()` helper rather than `->value` to get the plain string. No gateway client/integration exists yet beyond this enum and the visa-related `Setting` columns/fees used in `OrderController::getFinalInfo`.

## Gotchas
- **`ActiveStatus` enum namespace casing**: `app/Enums/ActiveStatus.php` declares `namespace App\enums;` (lowercase), unlike every other file in `app/Enums` which uses `App\Enums`. Existing call sites correctly import it as `use App\enums\ActiveStatus;` to match. This works on Windows/macOS (case-insensitive filesystems) but will break PSR-4 autoloading on case-sensitive Linux unless the import casing matches the class's declared namespace exactly — don't "fix" the import to `App\Enums\ActiveStatus` without also moving/renaming the file.
- **`WhatsAppNumberController` references a non-existent model**: it imports and calls `App\Models\WhatsAppNumber::all()`, but no such model exists — the actual model is `App\Models\Number` (rows are discriminated by a `type` column using `App\Enums\NumberType::WhatsApp`/`Phone`). Hitting `GET /whatsapp-numbers` currently throws a class-not-found error.
