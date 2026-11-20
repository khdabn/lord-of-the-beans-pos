# Lord of the Beans

A complete Laravel point-of-sale project for **IT0049 — Complete Point-of-Sale System**.
Prepared for **Group 4**: Butad, Carlos Joaquin A.; Cafugauan, Mc Kenrick L.; Buen, Angelo P.; Orlanda, Mark John M. The café interface uses espresso brown, cream, and caramel accents with original coffee and pastry illustrations.

## Features

- Staff login/logout, session renewal, login throttling, and authenticated management routes.
- Product creation, search, editing, stock management, image upload, archiving, and restoration.
- Customer creation, search, editing, and removal from the active directory.
- Staff account creation, editing, avatar uploads, password hashing, and removal of access.
- A single-product sale with an optional customer, integer quantity, and server-calculated total.
- Inventory reduction and sale insertion in one database transaction, with product row locking.
- Repeated submissions reuse the original sale instead of deducting stock twice.
- An old product-edit form cannot overwrite stock changed by a completed sale.
- Sales history with date/search filters and printable internal sales records.
- Overview with today's revenue, transaction/unit counts, a seven-day chart, and low-stock products.
- Original bundled SVG demo artwork and functional default images. No CDN or npm build.
- Private, re-encoded image uploads served through an authenticated route. No storage symlink.

## Local setup: Windows / XAMPP

Requirements: **64-bit PHP 8.2+**, Composer, MySQL/MariaDB, and extensions GD, PDO MySQL, PDO SQLite, mbstring, openssl, fileinfo, DOM/XML, tokenizer, and ZIP. PHP SQLite is used only by tests; the application uses MySQL. Keep PHP's upload limit at least 2 MB and post limit at least 8 MB.

1. Extract this project as `C:\xampp\htdocs\soko-pos` and open the folder in VS Code.
2. Start MySQL in XAMPP. Apache is needed only for local phpMyAdmin if you use `php artisan serve`.
3. Run:

   ```powershell
   php scripts/setup.php
   ```

   The first run creates `.env` and stops deliberately so you can edit it.
4. Set `ADMIN_PASSWORD` in `.env` to your own password of at least 12 characters. Double-quote passwords containing spaces or `#`; escape special dotenv characters as appropriate. Review the local MySQL settings. Default database: `soko_pos`, username: `root`.
5. Run `php scripts/setup.php` again. This installs Composer dependencies, creates `composer.lock`, generates the application key if missing, and clears local caches.
6. Create an **empty** `soko_pos` database in local phpMyAdmin. Choose `utf8mb4_unicode_ci`.
7. Run one command at a time:

   ```powershell
   php artisan migrate --seed
   php artisan test
   php artisan serve
   ```

8. Open **http://127.0.0.1:8000**. Username: **group4**. Password: the one you chose in `.env`. Keep the serve terminal open.

The source ZIP intentionally excludes `vendor/`, `composer.lock`, private configuration, uploads, and real customer data. Composer resolves supported Laravel 12 dependencies on the first install. Commit the resulting `composer.lock` to GitHub and use it for deployment. Dependency downloads need an internet connection. Do not use `--ignore-platform-reqs`.

The seeder creates one staff account, six coffee and pastry menu items, and three fictional customers. It creates **no sales**, so the dashboard starts with zero revenue. Re-running the seeder does not reset existing stock or passwords. Change staff passwords through the Staff screen; editing `ADMIN_PASSWORD` after seeding does not reset an existing account.

## Daily use

1. Sign in. Open **Products** to create or update inventory.
2. Optionally add a customer under **Customers**.
3. Open **Record sale**. Select one product, choose a quantity, and optionally a customer.
4. Press **Record sale**. The server checks the current stock and price and redirects to the saved sales record.
5. Open **Sales history** to filter or print transactions. Sales are immutable in the application.

All active staff have the same management permissions, matching the activity's shared staff access model. Staff accounts are created by existing staff; there is no public registration or email password reset. You cannot remove your currently signed-in account.

## Data integrity and schema

The four required tables and relationships are preserved. `id`/foreign keys use Laravel's matching unsigned BIGINT types. Required names, prices, quantity fields, and `created_at` are present. Extra fields support safe deletion and historical records:

| Table | Additions and reason |
| --- | --- |
| products | `is_archived`, `updated_at`; archive inventory without breaking sales |
| customers | `deleted_at`, `updated_at`; remove active records while preserving references |
| users | `deleted_at`, `updated_at`, `remember_token`; revoke accounts and preserve staff references |
| sales | `unit_price`, product/customer/staff name snapshots, unique `request_token`, `updated_at`; preserve original facts and reject duplicates |

Customer and staff deletion is a soft delete. Their active directory entries disappear; removed staff cannot authenticate. Their existing sales remain linked. Names on old receipts remain unchanged when names are edited later.

Prices and totals use `DECIMAL(10,2)`. `SaleService` calculates totals in integer centavos using `Money`; it never trusts a posted total or staff ID. A sale is limited to one product and PHP 99,999,999.99. Quantity must be a positive integer and cannot exceed stock. Product edits allow up to 1,000,000 units.

`SaleService` locks the cashier, product, and selected customer within a transaction. InnoDB row locks protect checkout on MySQL/MariaDB. Stock and sales both roll back when the transaction fails. SQLite feature tests check application behavior; they do not prove MySQL's concurrent locking behavior. A manual two-session stock test is in the acceptance checklist.

## Image handling

Uploads accept JPG, PNG, and WebP only, up to 2 MB, at most 6000 pixels on either side, and at most 16 megapixels. GD decodes and re-encodes every upload to JPEG, removes embedded metadata, and assigns a random UUID filename. Menu photos fit a 720 × 720 neutral canvas; staff avatars are center-cropped to 256 × 256. Existing upload files are removed when replaced or explicitly cleared. Static bundled SVG illustrations are authored assets; user SVG uploads are rejected. Images are stored in `storage/app/private/media` and retrieved through `/media/{filename}` after authentication.

## Tests and verification

```powershell
php artisan test
composer audit
```

The supplied feature tests cover route protection, valid/invalid/throttled login, removed staff, all screens, product/customer/staff CRUD, image handling, validation, hashing, self-removal prevention, stale stock edits, price tampering, overselling, duplicate submission, stock exhaustion, overflow, rollback, history snapshots, search, date filters, and pagination.

**Build verification status:** JavaScript syntax, checkout controls in an isolated DOM fixture, and source/package consistency were checked while preparing this project. PHP, Composer, and a usable browser runtime were unavailable in the preparation environment. Therefore Laravel tests, PHP linting, live browser rendering, and MySQL execution are **pending on your local machine**. No passing backend test result or deployment is claimed. Run the commands above and follow `docs/ACCEPTANCE_CHECKLIST.md` before submission.

## GitHub and hosting

Follow **[docs/GITHUB_AND_INFINITYFREE.md](docs/GITHUB_AND_INFINITYFREE.md)**. The source remains separate from your earlier Laravel POS and TSA projects.

After local tests pass, build the production ZIP:

```powershell
php scripts/prepare-deploy.php
```

The builder checks tests and dependency advisories, stages a fresh copy, installs production dependencies, includes your existing uploaded images, verifies ZIP contents, and produces `deploy-output/SOKO-InfinityFree.zip`. It excludes `.env`, sessions, logs, tests, and development dependencies. Export/import MySQL separately. The hosting ZIP is for File Manager; raw source plus the lockfile is for GitHub.

## Project map

| Location | Responsibility |
| --- | --- |
| `routes/web.php` | Authentication and protected resource routes |
| `app/Http/Controllers` | Page queries and HTTP actions |
| `app/Http/Requests` | Validated forms |
| `app/Models` | Relationships, casting, and archive behavior |
| `app/Services/SaleService.php` | Atomic checkout and stock integrity |
| `app/Services/ImageService.php` | Image conversion and storage |
| `database/migrations`, `database/seeders` | Reproducible database schema and demo data |
| `resources/views` | Blade pages and components |
| `public/css`, `public/js`, `public/images` | Responsive design and local assets |
| `tests/Feature/PosWorkflowTest.php` | Behavioral feature tests |
| `scripts`, `deployment` | Local setup and hosting preparation |

## Project scope

This is the activity's inventory and sales-recording application. It does not process payments, calculate VAT, handle refunds, or create official tax invoices. Its printable record is labeled accordingly. It uses one PHP currency and the Asia/Manila time zone. Adapt branding in `resources/views/layouts/app.blade.php`, `auth/login.blade.php`, and `sales/show.blade.php` if your group uses a different store name.

## References

- [Laravel 12 authentication](https://laravel.com/docs/12.x/authentication)
- [Laravel database transactions](https://laravel.com/docs/12.x/database)
- [Laravel pessimistic locking](https://laravel.com/docs/12.x/queries#pessimistic-locking)
- [PHP GD](https://www.php.net/manual/en/book.image.php)
- [InfinityFree Laravel deployment guide](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578)

See [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md) for the activity-to-code mapping and [LICENSE](LICENSE) for the original application code license. Laravel and Composer dependencies retain their respective licenses.

## Group 4 café update

Existing installations: follow `CAFE_UPDATE.md`. The original 27-test suite passed locally with 221 assertions after the date-fixture correction. This update retains that correction and adds an upgrade test; rerun the full suite locally before deployment.
