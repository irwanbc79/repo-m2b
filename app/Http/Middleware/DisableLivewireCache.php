<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableLivewireCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('livewire/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
            $response->headers->set('X-LiteSpeed-Purge', 'all');
        }

        return $response;
    }
}
