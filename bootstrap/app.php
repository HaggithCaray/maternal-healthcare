<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

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
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
            \App\Http\Middleware\PreventCachingOfPrivatePages::class,
        ]);

        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_PROTO |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PREFIX,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A rate limit says how long to wait; forms go back to the page with that message
        // instead of showing a bare "429 Too Many Requests" page.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            $seconds = (int) ($e->getHeaders()['Retry-After'] ?? 60);
            $wait = $seconds >= 120 ? ceil($seconds / 60) . ' minutes' : $seconds . ' seconds';
            $message = "Too many attempts. Please wait {$wait} and try again.";

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $message], 429, $e->getHeaders());
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', 'file']))
                ->withErrors(['throttle' => $message])
                ->with('error', $message);
        });
    })->create();
