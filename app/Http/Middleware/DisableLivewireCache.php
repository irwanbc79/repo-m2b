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

        // Endpoint dinamis yang TIDAK boleh di-cache LiteSpeed:
        // - livewire/*    : update/upload Livewire
        // - lw-update     : route update Livewire custom (hasil override)
        // - admin/inbox/* : body email (iframe), attachment, download
        //   -> mencegah LiteSpeed menyajikan 404/konten basi di dalam iframe inbox
        $noCache = $request->is('livewire/*')
            || $request->is('lw-update')
            || $request->is('admin/inbox/*');

        if ($noCache) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
            $response->headers->set('X-LiteSpeed-Purge', 'all');
        }

        return $response;
    }
}
