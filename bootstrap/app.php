<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Polyfills para servidores donde ext-mbstring no esté activo
if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth(string $string, int $start, int $width, string $trim_marker = '', ?string $encoding = null): string {
        if (strlen($string) <= $width) {
            return $string;
        }
        $markerLen = strlen($trim_marker);
        if ($width <= $markerLen) {
            return substr($trim_marker, 0, $width);
        }
        return substr($string, $start, max(0, $width - $markerLen)) . $trim_marker;
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int {
        return strlen($string);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null): int|false {
        return strpos($haystack, $needle, $offset);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string {
        return strtolower($string);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string {
        return strtoupper($string);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'ml-brain/*',
            'settings/*',
            'woocommerce/*',
            'campaigns/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->has('debug') || $request->is('debug*') || config('app.debug')) {
                return response(
                    "<div style='font-family: -apple-system, sans-serif; padding: 28px; background: #FFF1F2; color: #991B1B; border: 2px solid #F87171; border-radius: 12px; margin: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);'>" .
                    "<h2 style='margin-top: 0; color: #B91C1C;'>🚨 Diagnóstico de Error 500 Suitable</h2>" .
                    "<p style='font-size: 16px;'><strong>Mensaje:</strong> <code style='background: white; padding: 4px 8px; border-radius: 4px; border: 1px solid #FECACA;'>" . htmlspecialchars($e->getMessage()) . "</code></p>" .
                    "<p><strong>Archivo:</strong> <code>" . htmlspecialchars($e->getFile()) . "</code> (Línea: <strong>" . $e->getLine() . "</strong>)</p>" .
                    "<h4 style='margin-bottom: 8px;'>Trace completo:</h4>" .
                    "<pre style='background: #FFFFFF; padding: 16px; border: 1px solid #FECACA; border-radius: 8px; overflow-x: auto; font-size: 12px; line-height: 1.5; color: #1F2937; max-height: 400px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>" .
                    "</div>",
                    500
                );
            }
        });
    })->create();
