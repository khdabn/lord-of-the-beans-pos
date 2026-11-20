# Lord of the Beans — Group 4 update

Members: Butad, Carlos Joaquin A.; Cafugauan, Mc Kenrick L.; Buen, Angelo P.; Orlanda, Mark John M.

## Apply to your existing project

1. Stop `php artisan serve` with Ctrl+C and close the website tabs.
2. Make a backup copy of `C:\xampp\htdocs\soko-pos`. Export the current `soko_pos_clean` database from phpMyAdmin as a backup.
3. Extract the update ZIP to a temporary folder. Copy the **contents of update-files** into `C:\xampp\htdocs\soko-pos` and choose Replace for matching files. Merge folders. Do not replace the entire project directory with update-files: this package contains only changed/new files.
4. Keep your existing `.env`, `vendor`, `composer.lock`, and uploads. The ZIP contains no replacements for them. Open your existing `.env` and set these values (retain your password, key, and database settings):

   ```dotenv
   APP_NAME="Lord of the Beans"
   APP_TIMEZONE=Asia/Manila
   ADMIN_USERNAME=group4
   ADMIN_FULL_NAME="Group 4"
   ```

   Your working local database is `soko_pos_clean`; keep that value. `.env.example` is only a template and does not replace `.env`.
5. In the existing project terminal, run one command at a time:

   ```powershell
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   php artisan soko:cafe-upgrade
   php artisan test
   php artisan serve
   ```

6. Open http://127.0.0.1:8000. Sign in as **group4** with the **same password** you used before. Refresh with Ctrl+F5 if the old style is cached.
7. Check **Café menu**, **Record sale**, and **Group 4**. There are six new coffee/pastry items and all four names on the group page.

## What the database command does

- Renames the original `buen` account to `group4` and changes its display name to Group 4. Its password and ID remain intact. If group4 already belongs to another account, the command stops and rolls back; resolve that conflict before retrying.
- Archives only the six original demo products whose names and demo image filenames match. They remain under Archived products; existing sales remain linked.
- Adds Espresso, Iced Americano, Spanish Latte, Matcha Latte, Butter Croissant, and Chocolate Cookie if those names are not already present. Re-running does not reset their prices or stock.
- Leaves customer records, other staff accounts, uploads, and historical sale snapshots intact. A real member name can still appear on an old receipt because it records who made that sale.

The member's surname Buen remains in the group credits. Other occurrences in the upgrade command/tests identify the original account for conversion, not website branding.

## Verification and next steps

The original application passed 27 tests/221 assertions on your machine. One upgrade test is added here to check the account rename, preserved password/history, repeatability, menu stock, and group page. The updated suite is pending local execution. Source structure and bundled SVG checks were run during preparation; browser rendering and PHP execution were unavailable here.

No new database schema or Composer dependency is required. Do not run migrate:fresh, reinstall the project, or rerun setup. After the updated tests and pages work, continue with the included GitHub and InfinityFree guide. The hosting ZIP builder will include these files and the exported database will contain your café menu.
