<?php

namespace Growats\OkNicOwaspSecurity;

use Illuminate\Support\ServiceProvider;

class OwaspSecurityServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/owasp-security.php' => config_path('owasp-security.php'),
        ]);

        $this->app['router']->aliasMiddleware('owasp.security.headers', Middleware\SecurityHeaders::class);
        $this->app['router']->aliasMiddleware('owasp.security.xss', Middleware\XssSanitization::class);
        $this->app['router']->aliasMiddleware('owasp.security.rate', Middleware\RateLimiting::class);
        $this->app['router']->aliasMiddleware('owasp.security.sql', Middleware\SqlInjectionProtection::class);
        $this->app['router']->aliasMiddleware('owasp.security.cors', Middleware\CorsProtection::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/owasp-security.php', 'owasp-security');
    }
}
