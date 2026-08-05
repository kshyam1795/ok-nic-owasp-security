<?php

namespace Growats\OkNicOwaspSecurity;

use Growats\OkNicOwaspSecurity\Commands\InstallOwaspSecurity;
use Growats\OkNicOwaspSecurity\Middleware\AuthenticationProtection;
use Growats\OkNicOwaspSecurity\Middleware\CorsProtection;
use Growats\OkNicOwaspSecurity\Middleware\OtpVerification;
use Growats\OkNicOwaspSecurity\Middleware\RateLimiting;
use Growats\OkNicOwaspSecurity\Middleware\RoleMiddleware;
use Growats\OkNicOwaspSecurity\Middleware\SecurityHeaders;
use Growats\OkNicOwaspSecurity\Middleware\SessionSecurity;
use Growats\OkNicOwaspSecurity\Middleware\SqlInjectionProtection;
use Growats\OkNicOwaspSecurity\Middleware\XssSanitization;
use Growats\OkNicOwaspSecurity\Services\AuthSecurityService;
use Growats\OkNicOwaspSecurity\Services\OtpService;
use Growats\OkNicOwaspSecurity\Services\RoleService;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class OwaspSecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/owasp-security.php', 'owasp-security');

        $this->app->singleton(SecuritySettingsService::class);
        $this->app->singleton(OtpService::class);
        $this->app->singleton(AuthSecurityService::class);
        $this->app->singleton(RoleService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/Config/owasp-security.php' => config_path('owasp-security.php'),
        ], 'owasp-security-config');

        $this->publishes([
            __DIR__ . '/Config/owasp-security.php' => config_path('owasp-security.php'),
        ], 'owasp-security');

        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'owasp-security');

        $this->registerMiddleware();
        $this->registerCommands();
        $this->pushGlobalMiddleware();
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        $router->aliasMiddleware('owasp.security.headers', SecurityHeaders::class);
        $router->aliasMiddleware('owasp.security.xss', XssSanitization::class);
        $router->aliasMiddleware('owasp.security.rate', RateLimiting::class);
        $router->aliasMiddleware('owasp.security.sql', SqlInjectionProtection::class);
        $router->aliasMiddleware('owasp.security.cors', CorsProtection::class);
        $router->aliasMiddleware('owasp.security.session', SessionSecurity::class);
        $router->aliasMiddleware('owasp.security.auth', AuthenticationProtection::class);
        $router->aliasMiddleware('owasp.otp', OtpVerification::class);
        $router->aliasMiddleware('owasp.role', RoleMiddleware::class);
    }

    protected function pushGlobalMiddleware(): void
    {
        if (!config('owasp-security.enabled', true) || !config('owasp-security.auto_middleware', true)) {
            return;
        }

        $stack = [
            SecurityHeaders::class,
            SessionSecurity::class,
            XssSanitization::class,
            SqlInjectionProtection::class,
            RateLimiting::class,
            CorsProtection::class,
            AuthenticationProtection::class,
        ];

        foreach ($stack as $middleware) {
            $this->app['router']->pushMiddlewareToGroup('web', $middleware);
            $this->app['router']->pushMiddlewareToGroup('api', $middleware);
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallOwaspSecurity::class,
            ]);
        }
    }
}
