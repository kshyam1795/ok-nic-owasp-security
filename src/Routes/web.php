<?php

use Growats\OkNicOwaspSecurity\Http\Controllers\OtpController;
use Growats\OkNicOwaspSecurity\Http\Controllers\RoleController;
use Growats\OkNicOwaspSecurity\Http\Controllers\SecuritySettingsController;
use Illuminate\Support\Facades\Route;

$prefix = config('owasp-security.route_prefix', 'owasp-security');
$middleware = config('owasp-security.route_middleware', ['web', 'auth']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('owasp.')
    ->group(function () {
        Route::get('/', [SecuritySettingsController::class, 'dashboard'])->name('dashboard');
        Route::get('/settings', [SecuritySettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [SecuritySettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/{key}/toggle', [SecuritySettingsController::class, 'toggle'])->name('settings.toggle');
        Route::get('/audit', [SecuritySettingsController::class, 'auditLogs'])->name('audit');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles');
        Route::post('/roles/assign', [RoleController::class, 'assign'])->name('roles.assign');
        Route::post('/roles/revoke', [RoleController::class, 'revoke'])->name('roles.revoke');

        Route::get('/otp', [OtpController::class, 'challenge'])->name('otp.challenge');
        Route::post('/otp/send', [OtpController::class, 'send'])->name('otp.send');
        Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
    });

Route::middleware(['web', 'auth'])
    ->prefix(config('owasp-security.api_prefix', 'api/owasp-security'))
    ->name('owasp.api.')
    ->group(function () {
        Route::get('/settings', [SecuritySettingsController::class, 'apiIndex'])->name('settings');
    });
