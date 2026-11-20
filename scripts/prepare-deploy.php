<?php
// Creates a clean, reproducible hosting ZIP; never modifies source vendor/.env.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
chdir($root);
function failBuild(string $message): never { fwrite(STDERR,"\nBUILD STOPPED: $message\n"); exit(1); }
if (!class_exists(ZipArchive::class)) failBuild('Enable the PHP zip extension first.');
if (!is_file($root.'/composer.lock')) failBuild('Run setup locally first. composer.lock is required for a reproducible build.');
if (!is_dir($root.'/vendor')) failBuild('Run composer install locally first.');
function runBuildCommand(string $command): void {
    echo "\n> $command\n"; passthru($command,$code);
    if ($code !== 0) failBuild('The command above failed. No deployment package was published.');
}
$php = escapeshellarg(PHP_BINARY);
runBuildCommand("$php artisan config:clear");
runBuildCommand("$php artisan test");
runBuildCommand('composer audit --no-dev');
$out = $root.'/deploy-output';
if (!is_dir($out) && !mkdir($out,0775,true)) failBuild('Could not create deploy-output.');
// Unique build directories preserve earlier packages and avoid destructive cleanup.
$build = $out.'/build-'.date('Ymd-His').'-'.bin2hex(random_bytes(2));
$stage = $build.'/htdocs';
mkdir($stage,0775,true);
function copyDirectory(string $source, string $destination): void {
    if (!is_dir($destination)) mkdir($destination,0775,true);
    foreach (new DirectoryIterator($source) as $file) {
        if ($file->isDot() || $file->isLink()) continue;
        $target = $destination.'/'.$file->getFilename();
        if ($file->isDir()) copyDirectory($file->getPathname(),$target);
        elseif (!copy($file->getPathname(),$target)) failBuild('Could not copy '.$file->getPathname());
    }
}
foreach (['app','config','database','public','resources','routes'] as $folder) copyDirectory($root.'/'.$folder,$stage.'/'.$folder);
mkdir($stage.'/bootstrap',0775,true);
foreach (['app.php','providers.php'] as $file) copy($root.'/bootstrap/'.$file,$stage.'/bootstrap/'.$file);
foreach (['bootstrap/cache','storage/app/private/media','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs'] as $folder) {
    if (!is_dir($stage.'/'.$folder)) mkdir($stage.'/'.$folder,0775,true);
    file_put_contents($stage.'/'.$folder.'/.gitignore',"*\n!.gitignore\n");
}
copyDirectory($root.'/storage/app/private/media',$stage.'/storage/app/private/media');
foreach (['artisan','composer.json','composer.lock'] as $file) copy($root.'/'.$file,$stage.'/'.$file);
copy($root.'/deployment/infinityfree.htaccess',$stage.'/.htaccess');
chdir($stage);
runBuildCommand('composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction');
runBuildCommand('composer check-platform-reqs --no-dev');
// Package scripts may generate discovery caches; keep those, but never environment caches.
foreach (['config.php','routes-v7.php','events.php'] as $cache) {
    if (is_file($stage.'/bootstrap/cache/'.$cache)) unlink($stage.'/bootstrap/cache/'.$cache);
}
$zipPath = $out.'/SOKO-InfinityFree-'.basename($build).'.zip';
$zip = new ZipArchive;
if ($zip->open($zipPath,ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) failBuild('Could not create ZIP.');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
foreach ($iterator as $file) {
    $relative = str_replace('\\','/',substr($file->getPathname(),strlen($stage)+1));
    if ($file->isDir()) $zip->addEmptyDir($relative);
    elseif (!$zip->addFile($file->getPathname(),$relative)) failBuild('Could not add '.$relative.' to ZIP.');
}
if (!$zip->close()) failBuild('Could not finish ZIP. Check disk space.');
$check = new ZipArchive;
if ($check->open($zipPath) !== true) failBuild('ZIP verification failed.');
foreach (['.htaccess','public/.htaccess','vendor/autoload.php','composer.lock','public/css/app.css','public/js/app.js'] as $required) {
    if ($check->locateName($required) === false) failBuild('ZIP is missing '.$required);
}
if ($check->locateName('.env') !== false) failBuild('ZIP must not contain .env.');
$count = $check->numFiles;
$check->close();
if (!copy($zipPath,$out.'/SOKO-InfinityFree.zip')) failBuild('Could not publish the final ZIP. The timestamped ZIP remains available.');
echo "\nSUCCESS: $out/SOKO-InfinityFree.zip\n";
echo "Verified $count ZIP entries. The ZIP contains the CONTENTS of htdocs.\n";
echo "Includes uploaded product/avatar images; excludes .env, sessions, logs, tests, and dev dependencies.\n";
echo "Follow docs/GITHUB_AND_INFINITYFREE.md to export/import MySQL and configure the hosted .env.\n";
