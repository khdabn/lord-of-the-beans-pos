# Acceptance checklist

Use a local development database first. Automated checks run in an isolated in-memory SQLite database; they do not reset your MySQL records. Check these boxes only after running them.

## Local commands

- [ ] `php scripts/setup.php` completes after `.env` is configured.
- [ ] `php artisan migrate --seed` completes against the new `soko_pos` database.
- [ ] `php artisan test` passes all supplied tests.
- [ ] `composer audit` reports no unresolved advisories.
- [ ] `php artisan view:cache` compiles all Blade views; then `php artisan view:clear` removes compiled local views.
- [ ] `php artisan route:list` lists all required resource endpoints.

## Authentication and staff

- [ ] Guest access to every management screen redirects to login.
- [ ] Correct credentials sign in; incorrect credentials show a generic message.
- [ ] Repeated wrong attempts are throttled.
- [ ] Create a second staff account with matching passwords and an avatar.
- [ ] Avatar displays, and its stored file is a 256 × 256 JPEG.
- [ ] Edit staff details; leave passwords blank and confirm the password still works.
- [ ] Change a password; verify the new password works and the old one fails.
- [ ] Sign into a second browser session; change that account's password and confirm its old session loses access on the next request.
- [ ] Remove another staff account and confirm it cannot sign in.
- [ ] The current account cannot remove itself.

## Products and customers

- [ ] Create, list, search, and edit products.
- [ ] Reject an empty name, negative stock, negative price, and more than two decimal places.
- [ ] Upload JPG/PNG/WebP; stored product image is a 720 × 720 JPEG.
- [ ] Replace an image and remove an image; fallback artwork displays correctly.
- [ ] Reject SVG, PHP/text files, and uploads larger than 2 MB.
- [ ] Archive a product; it disappears from checkout. Restore it from Archived products.
- [ ] Create, list, search, edit, and remove customers; invalid email is rejected.

## Sales and inventory

- [ ] Create a test product with stock 3 and price 10.25.
- [ ] Record 2 units without a customer: total 20.50, remaining stock 1.
- [ ] Attempt 2 more: a clear error, no new sale, stock still 1.
- [ ] Record the last unit with a customer: total 10.25, stock 0.
- [ ] Zero, negative, and fractional quantities are rejected by the server.
- [ ] Submitting the same completed form again does not duplicate the sale.
- [ ] Receipt and sales history show product, customer or Walk-in, staff, quantity, price, total, and date.
- [ ] Rename a product/customer/staff member; past receipts keep the original names.
- [ ] Customer/staff removal and product archiving preserve old sales.
- [ ] Date/search filters and pagination show expected records.
- [ ] Print record produces a clean page without sidebar/navigation.

## Two-session MySQL test

- [ ] Give a test product stock 1.
- [ ] Open checkout in two different browser sessions, using two staff accounts.
- [ ] Select that product with quantity 1 in both, then submit near-simultaneously.
- [ ] Exactly one sale succeeds; the other is rejected; stock ends at 0, never below 0.
- [ ] Open product edit, sell an item in the other session, then try saving the old form. It must reject the stale stock count.

## Layout and hosting

- [ ] Check login, overview, products, checkout, forms, history, and receipt at desktop width.
- [ ] At phone width, navigation toggles, forms stay readable, and tables scroll within their panels.
- [ ] Keyboard users can reach controls, choose products, and submit forms.
- [ ] Hosting ZIP build reports SUCCESS and includes both `.htaccess` files plus `vendor/autoload.php`.
- [ ] Database export/import succeeds, and hosted login/CRUD/sales/images work.
- [ ] `.env`, logs, and dependency files cannot be downloaded from the live site.
- [ ] GitHub contains source, migrations, seeders, tests, and `composer.lock`; no secrets or vendor.
- [ ] README contains the actual live URL and honest verification results.
