#!/usr/bin/env php
<?php

/*
|--------------------------------------------------------------------------
| Windows/XAMPP Laravel Cache Repair Helper
|--------------------------------------------------------------------------
|
| This script does not bootstrap Laravel or load vendor files. It is safe to
| run when Laravel's cached bootstrap files or compiled Blade views are broken.
|
*/

$basePath = dirname(__DIR__);
$requiredPhpVersion = 80200;
$requiredExtensions = ['intl'];
$requiredDirectories = [
    'bootstrap/cache',
    'storage',
    'storage/framework/views',
    'storage/framework/cache',
    'storage/framework/sessions',
];

function line($message)
{
    fwrite(STDOUT, $message.PHP_EOL);
}

function fail($message)
{
    fwrite(STDERR, 'ERROR: '.$message.PHP_EOL);
}

function removeMatchingFiles($directory, $matcher)
{
    if (! is_dir($directory)) {
        return [0, []];
    }

    $removed = 0;
    $errors = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();

        if (! $matcher($path, $file)) {
            continue;
        }

        if (@unlink($path)) {
            $removed++;
            continue;
        }

        $errors[] = $path;
    }

    return [$removed, $errors];
}

line('Laravel Windows/XAMPP cache repair');
line('PHP CLI version: '.PHP_VERSION.' ('.PHP_BINARY.')');

if (PHP_VERSION_ID < $requiredPhpVersion) {
    fail('Project ini membutuhkan PHP 8.2 atau lebih baru. Upgrade/switch XAMPP Apache dan PHP CLI ke PHP 8.2+ sebelum menjalankan artisan.');
    exit(1);
}

foreach ($requiredExtensions as $extension) {
    if (extension_loaded($extension)) {
        line('[OK] PHP extension '.$extension.' aktif.');
        continue;
    }

    fail('PHP extension '.$extension.' belum aktif. Untuk Windows/XAMPP, buka php.ini yang dipakai Apache dan PHP CLI, aktifkan extension='.$extension.', restart Apache, lalu jalankan ulang script ini.');
    exit(1);
}

$hasPermissionError = false;

foreach ($requiredDirectories as $relativeDirectory) {
    $directory = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

    if (! is_dir($directory) && ! @mkdir($directory, 0775, true)) {
        fail('Tidak bisa membuat folder '.$relativeDirectory.'. Jalankan terminal sebagai user yang memiliki akses ke project.');
        $hasPermissionError = true;
        continue;
    }

    @chmod($directory, 0775);

    $readable = is_readable($directory);
    $writable = is_writable($directory);
    line(($readable && $writable ? '[OK] ' : '[CHECK] ').$relativeDirectory.' readable='.($readable ? 'yes' : 'no').' writable='.($writable ? 'yes' : 'no'));

    if (! $readable || ! $writable) {
        $hasPermissionError = true;
    }
}

list($cacheRemoved, $cacheErrors) = removeMatchingFiles(
    $basePath.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache',
    function ($path) {
        return substr($path, -4) === '.php';
    }
);
line('Removed bootstrap/cache/*.php files: '.$cacheRemoved);

list($viewRemoved, $viewErrors) = removeMatchingFiles(
    $basePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views',
    function ($path, $file) {
        return $file->getBasename() !== '.gitignore';
    }
);
line('Removed compiled view files: '.$viewRemoved);

foreach (array_merge($cacheErrors, $viewErrors) as $path) {
    fail('Tidak bisa menghapus '.$path.'. Pastikan Apache dan php artisan serve sudah berhenti, lalu hapus manual.');
    $hasPermissionError = true;
}

if (is_file($basePath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php')) {
    line('Running php artisan optimize:clear ...');
    passthru(escapeshellarg(PHP_BINARY).' artisan optimize:clear', $status);

    if ($status !== 0) {
        fail('php artisan optimize:clear gagal. Periksa pesan error di atas.');
        exit($status);
    }
} else {
    line('[SKIP] vendor/autoload.php belum ada, jadi php artisan optimize:clear tidak dijalankan. Jalankan composer install terlebih dahulu.');
}

if ($hasPermissionError) {
    fail('Masih ada masalah permission/cache lock. Stop Apache/php artisan serve, lalu ulangi script ini.');
    exit(1);
}

line('Selesai. Cache Laravel siap dibuat ulang.');
