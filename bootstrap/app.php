<?php

use App\Http\Middleware\EnsureStudentIsAuthenticated;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'student.auth' => EnsureStudentIsAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! config('app.debug') || $request->expectsJson()) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $escape = static fn (?string $value): string => htmlspecialchars(
                (string) $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $message = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : 'Tidak ada pesan error dari exception.';

            $content = '<!doctype html>'
                .'<html lang="id"><head><meta charset="utf-8">'
                .'<meta name="viewport" content="width=device-width, initial-scale=1">'
                .'<title>Application Error</title>'
                .'<style>body{font-family:ui-sans-serif,system-ui,sans-serif;margin:2rem;background:#f8fafc;color:#0f172a}'
                .'main{max-width:960px;margin:auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 8px 30px #0f172a14}'
                .'h1{margin-top:0;color:#b91c1c}code,pre{background:#f1f5f9;border-radius:8px}code{padding:2px 6px}pre{padding:16px;overflow:auto}'
                .'p{line-height:1.6}.muted{color:#475569}</style></head><body><main>'
                .'<h1>Application Error</h1>'
                .'<p class="muted">Renderer exception bawaan Laravel dilewati agar error asli tidak tertutup oleh error Blade vendor.</p>'
                .'<p><strong>Type:</strong> <code>'.$escape($exception::class).'</code></p>'
                .'<p><strong>Message:</strong></p><pre>'.$escape($message).'</pre>'
                .'<p><strong>Location:</strong> <code>'.$escape($exception->getFile()).':'.$escape((string) $exception->getLine()).'</code></p>'
                .'<p class="muted">Setelah error asli di atas diperbaiki, jalankan <code>php artisan optimize:clear</code>. Pastikan XAMPP memakai PHP 8.2+ untuk Laravel 12.</p>'
                .'</main></body></html>';

            return response($content, $status)->header('Content-Type', 'text/html; charset=UTF-8');
        });
    })->create();
