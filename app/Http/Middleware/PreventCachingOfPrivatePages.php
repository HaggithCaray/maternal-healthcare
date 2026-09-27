<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages and files shown to a signed-in user contain patient information: tell the browser not to
 * store them, so the Back button or the disk cache can't show them after logout on a shared device.
 */
class PreventCachingOfPrivatePages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
