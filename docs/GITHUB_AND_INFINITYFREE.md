# GitHub and InfinityFree deployment

Complete local setup in `README.md` and pass the tests first. Use a **new repository, domain, and database** to keep your earlier POS and TSA work intact.

## 1. Check the application locally

```powershell
php artisan test
composer audit
php artisan serve
```

Use `docs/ACCEPTANCE_CHECKLIST.md`. Keep your chosen staff credentials private. Close neither the serve terminal nor MySQL while using the local application.

## 2. Push raw source to GitHub

Create an **empty** GitHub repository named `soko-pos` under your `khdabn` account. Do not initialize it with another README, license, or gitignore. From `C:\xampp\htdocs\soko-pos`:

```powershell
git init
git add .
git status --short
git check-ignore .env vendor/autoload.php storage/logs/laravel.log deploy-output/SOKO-InfinityFree.zip
```

The last command should print all four paths. Confirm that `.env` is absent from the staged files and `composer.lock` is included. `.env.example` is safe; keep its `ADMIN_PASSWORD` and `APP_KEY` blank.

Then run:

```powershell
git commit -m "Complete IT0049 SOKO point-of-sale system"
git branch -M main
git remote add origin https://github.com/khdabn/soko-pos.git
git push -u origin main
git status
```

Use that remote only after creating the named repository. If you choose a different name, use GitHub's **Code → HTTPS** URL. If an `origin` already exists, inspect it using `git remote -v`; do not replace another project's remote accidentally. Windows LF/CRLF warnings are informational; `.gitattributes` supplies consistent source line endings.

## 3. Create a separate InfinityFree site and database

Create a new subdomain for this project in your InfinityFree account. Use the assigned domain and its File Manager `htdocs` directory. Create a new MySQL database, for example with suffix `sokopos`. Copy the **exact** database name, username, and MySQL hostname from the control panel. Hosting values can differ from earlier projects. Do not use `localhost` as the hosted database hostname unless the control panel explicitly supplies it.

In local phpMyAdmin, select `soko_pos` → **Export → Quick → SQL**. In hosted phpMyAdmin, select the new empty hosted database → **Import**, choose the exported file, and run the import. This transfers schema, products, customers, hashed staff passwords, and sales. It does not transfer image files; those go in the deployment ZIP.

Use a new empty hosted database. Importing an export into an existing live database can overwrite its contents. Do not run `migrate:fresh` on a site containing data you need.

## 4. Build the hosting ZIP locally

In a new terminal in the source folder:

```powershell
php scripts/prepare-deploy.php
```

Wait for the final line beginning **SUCCESS**. If the command fails, fix the displayed error and rerun it. Do not assume an old ZIP is the new build. The script verifies ZIP entries before publishing the filename.

The completed package is:

```text
deploy-output/SOKO-InfinityFree.zip
```

The staging copy is in a timestamped `deploy-output/build-.../htdocs` folder. Your source `.env` and development `vendor` remain unchanged. Packaging uses PHP's ZIP extension so it includes `.htaccess` and empty writable directories reliably.

## 5. Upload and extract

In the **new domain's `htdocs`** folder, use **Upload & Unzip** for `SOKO-InfinityFree.zip`. If that option is unavailable or extraction fails, use FTP to transfer the contents of the staged `htdocs` folder.

After extraction, `htdocs` must directly contain `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `vendor`, `.htaccess`, `artisan`, `composer.json`, and `composer.lock`. Do not create `htdocs/htdocs`. The root `.htaccess` sends requests to `public` and blocks private paths. `public/.htaccess` handles Laravel routes.

Check both `.htaccess` files are present; some file managers hide dotfiles. Delete any uploaded ZIP after extraction. The original private `.env` is intentionally not in the package.

## 6. Create the hosted .env

Create a file named `.env` in hosted `htdocs`. Use `.env.example` as your starting text, then set these fields in the hosted copy:

| Variable | Hosted value |
| --- | --- |
| `APP_NAME` | `"Lord of the Beans"` |
| `APP_ENV` | `production` |
| `APP_KEY` | Copy the complete generated key from your local `.env` privately |
| `APP_DEBUG` | `false` |
| `APP_URL` | Your actual new site's URL, including `https://` when HTTPS is working |
| `APP_TIMEZONE` | `Asia/Manila` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Exact MySQL hostname shown for the hosting account |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | Exact full newly created hosted database name |
| `DB_USERNAME` | Exact MySQL username from the panel |
| `DB_PASSWORD` | Actual MySQL password, quoted if needed |
| `SESSION_DRIVER` | `file` |
| `SESSION_SECURE_COOKIE` | `true` for an HTTPS site; `false` only while using HTTP |
| `CACHE_STORE` | `file` |
| `QUEUE_CONNECTION` | `sync` |
| `LOG_CHANNEL` | `stack` |
| `LOG_LEVEL` | `error` |

Do not publish your key or password, and do not paste a screenshot showing them. Omit `ADMIN_PASSWORD` in the hosted environment: your imported database already contains the hashed staff password. Do not generate a different application key on each update.

If HTTPS has not been activated yet, use the real HTTP site URL and `SESSION_SECURE_COOKIE=false` temporarily; switch both after the site's certificate is active. Using secure cookies over HTTP prevents login sessions from persisting.

## 7. Verify on the hosted site

1. Open the new site. It should display the Lord of the Beans login screen.
2. Sign in as `group4` using your chosen local password. The imported user row supplies the password hash.
3. Add a temporary product with stock 3 and price 10.00. Upload a JPG/PNG to test image processing.
4. Sell 2 units. Verify the saved total is PHP 20.00 and stock is now 1.
5. Try another sale of 2. It must be rejected without a new transaction or changed stock.
6. Check the receipt, sales history, customer directory, staff creation/editing/removal, and image display.
7. Log out. Direct visits to `/products`, `/customers`, `/staff`, and `/sales/create` must require login.
8. Verify `/.env`, `/storage/logs/laravel.log`, and `/composer.json` are inaccessible (403 or 404), never readable.

Any test sale remains in history by design. Label test products clearly, then archive them.

## 8. Finish submission

Add your actual hosted URL to the repository README. Record only tests and hosted checks that you personally ran successfully.

```powershell
git add README.md
git commit -m "Add live website and verified deployment notes"
git push
```

Submit the GitHub repository link and the live website link. Migrations and seeders are already included, satisfying the database-file requirement. Give the instructor login details privately if needed; do not place them in the public README.

## Troubleshooting

| Symptom | What to check |
| --- | --- |
| `php` not recognized | Use `C:\xampp\php\php.exe` or add XAMPP PHP to PATH; reopen the terminal |
| Missing extension | Run `php --ini`, enable the named extension in that file, restart the CLI; do not bypass platform checks |
| Composer timeout | Check internet access and retry; do not switch off TLS verification |
| Database access denied | Check MySQL is running and `.env` credentials match the selected local or hosted database |
| Unknown database | Create `soko_pos` locally before migrations; use the full hosted database name online |
| Empty application key | Run `php artisan key:generate` locally; privately copy that key to the hosted `.env` |
| Browser connection refused | Start `php artisan serve`, keep it running, and use the URL it prints |
| Login repeatedly returns to login | Confirm the URL scheme and secure-cookie value agree; check writable session directories |
| Images fail | Confirm hosted GD is enabled and `storage/app/private/media` was uploaded; images require login, no symlink |
| 419 expired page | Refresh the form and sign in again; check session storage and cookie settings |
| Stock changed error | Reload the product-edit page and review the latest count before saving |
| 500 on hosted site | Inspect `storage/logs/laravel.log` privately in File Manager; keep public debug off |
| 404 on every page | Check the domain's correct `htdocs`, both `.htaccess` files, and extraction depth |
| No hosting ZIP | Look for the script's final SUCCESS message; correct the first failure and rerun |
| PHPUnit missing | Run `composer install` in your source folder to restore development packages |

Never upload a local cache with production secrets or Windows paths. The builder creates clean caches. If manually changing hosting config, ensure `bootstrap/cache/config.php` is absent so the new `.env` is read. Keep `bootstrap/cache` and `storage` writable according to your hosting account's default permissions; do not use world-writable permissions as a blanket fix.

Official deployment background: [InfinityFree Laravel guide](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578).
