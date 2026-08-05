<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsProtection
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->settings->isEnabled('protection.cors', true)) {
            return $next($request);
        }

        $meta = $this->settings->all()['protection.cors']['meta']
            ?? config('owasp-security.protections.cors', []);

        $allowedOrigins = $meta['allowed_origins'] ?? [];
        if (is_string($allowedOrigins)) {
            $allowedOrigins = array_filter(array_map('trim', explode(',', $allowedOrigins)));
        }

        $origin = $request->headers->get('Origin');
        $allowOrigin = null;

        if (empty($allowedOrigins)) {
            // Safe default: same-origin only (no ACAO echo of arbitrary Origin)
            $allowOrigin = null;
        } elseif (in_array('*', $allowedOrigins, true)) {
            $allowOrigin = '*';
        } elseif ($origin && in_array($origin, $allowedOrigins, true)) {
            $allowOrigin = $origin;
        }

        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        if ($allowOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
            if ($allowOrigin !== '*') {
                $response->headers->set('Vary', 'Origin');
            }
        }

        $response->headers->set(
            'Access-Control-Allow-Methods',
            implode(', ', $meta['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])
        );
        $response->headers->set(
            'Access-Control-Allow-Headers',
            implode(', ', $meta['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'])
        );

        if (!empty($meta['supports_credentials']) && $allowOrigin && $allowOrigin !== '*') {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
