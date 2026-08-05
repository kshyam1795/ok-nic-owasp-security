<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!config('owasp-security.enabled', true)) {
            return $response;
        }

        $map = [
            'header.x_frame_options' => 'X-Frame-Options',
            'header.x_content_type_options' => 'X-Content-Type-Options',
            'header.x_xss_protection' => 'X-XSS-Protection',
            'header.strict_transport_security' => 'Strict-Transport-Security',
            'header.referrer_policy' => 'Referrer-Policy',
            'header.content_security_policy' => 'Content-Security-Policy',
            'header.permissions_policy' => 'Permissions-Policy',
            'header.cross_origin_opener_policy' => 'Cross-Origin-Opener-Policy',
            'header.cross_origin_resource_policy' => 'Cross-Origin-Resource-Policy',
            'header.cross_origin_embedder_policy' => 'Cross-Origin-Embedder-Policy',
        ];

        foreach ($map as $key => $header) {
            if ($this->settings->isEnabled($key, true)) {
                $value = $this->settings->value($key);
                if ($value !== null && $value !== '') {
                    $response->headers->set($header, $value);
                }
            }
        }

        if ($this->settings->isEnabled('header.cache_control', true)) {
            $paths = $this->settings->all()['header.cache_control']['meta']['paths']
                ?? config('owasp-security.headers.cache_control.paths', []);
            $path = $request->path();
            foreach ($paths as $needle) {
                if ($needle !== '' && str_contains($path, $needle)) {
                    $response->headers->set(
                        'Cache-Control',
                        $this->settings->value('header.cache_control', 'no-store, no-cache, must-revalidate, private')
                    );
                    break;
                }
            }
        }

        return $response;
    }
}
