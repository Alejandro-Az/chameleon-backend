<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega headers de seguridad HTTP recomendados por OWASP (A05 - Security Misconfiguration).
 *
 * - X-Content-Type-Options: previene MIME-type sniffing
 * - X-Frame-Options: previene clickjacking
 * - Referrer-Policy: limita filtración de información en el header Referer
 * - Permissions-Policy: deniega explícitamente capacidades de browser no requeridas
 * - Cache-Control: previene que proxies/browsers cacheen respuestas de API sensibles
 * - Strict-Transport-Security: fuerza HTTPS (solo en entornos no-locales)
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Las respuestas de la API no deben cachearse por proxies ni browsers
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');

        // HSTS solo cuando se sirve sobre HTTPS y fuera de local.
        if (config('app.env') !== 'local' && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
