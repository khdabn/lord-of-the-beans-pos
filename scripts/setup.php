<?php
// Run from the project root: php scripts/setup.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
function stop(string $message): never { fwrite(STDERR, "\nSETUP STOPPED: $message\n"); exit(1); }
if (PHP_VERSION_ID < 80200 || PHP_INT_SIZE < 8) stop('Use 64-bit PHP 8.2 or newer.');
foreach (['gd','pdo_mysql','pdo_sqlite','mbstring','openssl','fileinfo','dom','xml','tokenizer','zip'] as $extension) {
    if (!extension_loaded($extension)) stop("Enable extension $extension in your php.ini, then run this command again. Run php --ini to find that file.");
}
if (!is_file('.env')) {
    if (!copy('.env.example','.env')) stop('Could not create .env. Check folder permissions.');
    echo "Created .env. Open it in VS Code now.\n";
    echo "Set ADMIN_PASSWORD to your own password of at least 12 characters.\n";
    echo "Review DB_DATABASE=soko_pos and your MySQL credentials.\n";
    echo "Then run: php scripts/setup.php\n";
    exit(0);
}
if (!preg_match('/^ADMIN_PASSWORD=(.+)$/m',file_get_contents('.env'),$match) || strlen(trim($match[1]," \r\n\t\"'")) < 12) {
    stop('Set ADMIN_PASSWORD in .env to at least 12 characters. Do not share this file or password.');
}
function runCommand(string $command): void {
    echo "\n> $command\n"; passthru($command,$code);
    if ($code !== 0) stop('The command above failed. Fix that error and rerun setup.');
}
runCommand('composer install --prefer-dist --no-interaction');
$php = escapeshellarg(PHP_BINARY);
if (!preg_match('/^APP_KEY=[\x20\t]*[\x22\x27]?base64:/m',file_get_contents('.env'))) runCommand("$php artisan key:generate --ansi");
runCommand("$php artisan config:clear");
runCommand("$php artisan view:clear");
echo "\nSetup complete. Create the EMPTY database soko_pos in local phpMyAdmin.\n";
echo "Then run these commands, one at a time:\n";
echo "  php artisan migrate --seed\n  php artisan test\n  php artisan serve\n";
echo "Open http://127.0.0.1:8000 and log in as group4 with the password you chose.\n";
