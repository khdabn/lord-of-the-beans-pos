# Activity-to-code mapping

Based on the attached **IT0049 — Complete Point-of-Sale System (Laravel)** laboratory document.

| Required behavior | Implementation |
| --- | --- |
| Required four-table relational schema | `database/migrations/2026_01_01_000000_create_pos_tables.php` |
| Product list, add, edit, archive | `ProductController`, `ProductRequest`, `resources/views/products` |
| Product upload prepared for display | `ImageService`, 720 × 720 JPEG, authenticated `MediaController` |
| Customer full CRUD | `CustomerController`, `CustomerRequest`, `resources/views/customers`; soft deletion preserves sale relationships |
| Staff full CRUD | `StaffController`, `StaffRequest`, `resources/views/staff`; removal revokes access |
| Staff avatar preparation | `ImageService`, 256 × 256 JPEG |
| Hashed staff passwords | User model `hashed` cast and confirmed password validation |
| Authentication for every management page | `AuthController`, `auth` and `auth.session` middleware in `routes/web.php` |
| Product, optional customer, positive quantity sale form | `resources/views/sales/create.blade.php`, `SaleRequest` |
| Sale decreases stock and rejects overselling | `SaleService`: row lock, validation, calculation, update and insert inside a database transaction |
| Sales history with required fields | `SaleController`, `resources/views/sales`; original snapshots plus intact foreign keys |
| GitHub source with migrations/seeders or SQL | Entire source project plus migrations/seeders; generated `composer.lock` committed after setup |
| Hosted working version | Hosting package builder and `GITHUB_AND_INFINITYFREE.md`; must be deployed and verified in the user's hosting account |

## Design decisions

The required sale schema is one product per sale, so checkout intentionally records one product per transaction. There is no unsupported cart/order schema. Prices are fixed-point decimals and arithmetic is in integer centavos. Existing sale rows are not editable through the interface.

Products archive through `is_archived`; customers and users use `deleted_at` to preserve foreign keys and historical links. Required columns remain present. Additional sale snapshots prevent later product or name changes from rewriting the past. No fake revenue or fabricated transactions are seeded.

All active staff share management permissions because the assignment specifies staff authentication without a role hierarchy. Removed staff are excluded by the authentication provider's Eloquent soft-delete scope. Password changes are enforced by session authentication checks on subsequent protected requests.

Original illustrations, local CSS, responsive layouts, inline SVG icons, visible focus styles, form labels, pagination, errors, success notices, and print styling are included.
