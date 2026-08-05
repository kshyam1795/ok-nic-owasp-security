<?php

namespace Growats\OkNicOwaspSecurity\Http\Controllers;

use Growats\OkNicOwaspSecurity\Models\SecurityAuditLog;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SecuritySettingsController extends Controller
{
    public function __construct(
        protected SecuritySettingsService $settings
    ) {}

    public function dashboard()
    {
        $all = $this->settings->all();
        $stats = [
            'total' => count($all),
            'enabled' => collect($all)->where('enabled', true)->count(),
            'disabled' => collect($all)->where('enabled', false)->count(),
            'headers' => collect($all)->where('group', 'headers')->count(),
            'authentication' => collect($all)->where('group', 'authentication')->count(),
            'protections' => collect($all)->where('group', 'protections')->count(),
        ];

        return view('owasp-security::dashboard', compact('stats', 'all'));
    }

    public function index(Request $request)
    {
        $group = $request->query('group');
        $settings = $this->settings->all($group);
        $groups = [
            'headers' => 'HTTP Security Headers',
            'authentication' => 'Authentication & Session',
            'protections' => 'Input / Output Protections',
        ];

        return view('owasp-security::settings', compact('settings', 'groups', 'group'));
    }

    public function update(Request $request)
    {
        $this->authorizeSettings($request);

        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.enabled' => 'sometimes|boolean',
            'settings.*.value' => 'nullable|string',
        ]);

        // Normalize checkbox booleans
        $items = [];
        foreach ($data['settings'] as $item) {
            $items[] = [
                'key' => $item['key'],
                'enabled' => filter_var($item['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'value' => $item['value'] ?? null,
            ];
        }

        $this->settings->bulkUpdate($items, $request->user()?->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Settings updated successfully.', 'settings' => $this->settings->all()]);
        }

        return back()->with('success', 'Security settings updated. Changes apply immediately.');
    }

    public function toggle(Request $request, string $key)
    {
        $this->authorizeSettings($request);

        $enabled = filter_var($request->input('enabled', true), FILTER_VALIDATE_BOOLEAN);
        $setting = $this->settings->toggle($key, $enabled, $request->user()?->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Setting toggled.', 'setting' => $setting]);
        }

        return back()->with('success', "{$setting->label} " . ($enabled ? 'enabled' : 'disabled') . '.');
    }

    public function auditLogs(Request $request)
    {
        $logs = SecurityAuditLog::query()
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->latest('id')
            ->paginate(50);

        return view('owasp-security::audit', compact('logs'));
    }

    public function apiIndex(Request $request)
    {
        return response()->json([
            'settings' => $this->settings->all($request->query('group')),
        ]);
    }

    protected function authorizeSettings(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if (method_exists($user, 'hasOwaspPermission') && !$user->hasOwaspPermission('settings.update')) {
            abort(403, 'You do not have permission to update security settings.');
        }

        if (method_exists($user, 'hasOwaspRole') && !$user->hasOwaspRole(['super_admin', 'security_admin'])) {
            abort(403);
        }
    }
}
