<?php

namespace Growats\OkNicOwaspSecurity\Middleware;

use Closure;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionSecurity
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->settings->isEnabled('auth.session', true)) {
            return $next($request);
        }

        $meta = $this->settings->all()['auth.session']['meta'] ?? [];
        $idle = (int) ($meta['idle_timeout_minutes'] ?? 30);
        $absolute = (int) ($meta['absolute_timeout_minutes'] ?? 480);

        if ($request->user()) {
            $last = session('owasp_last_activity_at');
            $started = session('owasp_session_started_at');

            if (!$started) {
                session(['owasp_session_started_at' => now()->timestamp]);
            } elseif ($absolute > 0 && (now()->timestamp - (int) $started) > ($absolute * 60)) {
                return $this->expire($request, 'Session absolute timeout exceeded.');
            }

            if ($last && $idle > 0 && (now()->timestamp - (int) $last) > ($idle * 60)) {
                return $this->expire($request, 'Session idle timeout exceeded.');
            }

            session(['owasp_last_activity_at' => now()->timestamp]);
        }

        if ($this->settings->isEnabled('auth.force_https', false) && !$request->secure() && !$this->isLocal($request)) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }

    protected function expire(Request $request, string $message)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['error' => $message, 'owasp' => 'A07:2021'], 401);
        }

        $login = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');

        return redirect()->guest($login)->withErrors(['session' => $message]);
    }

    protected function isLocal(Request $request): bool
    {
        return in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }
}
