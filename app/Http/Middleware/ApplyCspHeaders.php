<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyCspHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' https:; " .
                "style-src 'self' 'unsafe-inline' https:; " .
                "img-src 'self' data: https:; " .
                "font-src 'self' https:; " .
                "connect-src 'self' https:; " .
                "frame-ancestors 'none';"
            );
            
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'DENY');
            $response->header('X-XSS-Protection', '1; mode=block');
        }

        return $response;
    }
}