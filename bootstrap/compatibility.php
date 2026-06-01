<?php

/*
|--------------------------------------------------------------------------
| Runtime Compatibility Guard
|--------------------------------------------------------------------------
|
| This file is intentionally dependency-free so it can run before Composer
| autoloads Laravel. It prevents Laravel 12/vendor exception views from being
| parsed by an unsupported PHP version, which otherwise can surface as a vague
| Blade syntax error while rendering an exception.
|
*/

if (PHP_VERSION_ID < 80200) {
    $message = 'This Laravel application requires PHP 8.2 or newer. Current PHP: '.PHP_VERSION.'. '.
        'On Windows/XAMPP, switch Apache and CLI to PHP 8.2+ before running php artisan optimize:clear.';

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit(1);
}
