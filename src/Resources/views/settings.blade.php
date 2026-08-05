@extends('owasp-security::layout')

@section('title', 'Security Settings')

@section('content')
    <h1>Security Settings</h1>
    <p class="sub">Enable or disable controls mapped to OWASP Top 10 findings from security audits. Super Admin owns this panel.</p>

    @php
        $canUpdate = auth()->user()
            && (
                (method_exists(auth()->user(), 'hasOwaspPermission') && auth()->user()->hasOwaspPermission('settings.update'))
                || (method_exists(auth()->user(), 'isOwaspSuperAdmin') && auth()->user()->isOwaspSuperAdmin())
                || (method_exists(auth()->user(), 'hasOwaspRole') && auth()->user()->hasOwaspRole(['super_admin', 'security_admin']))
            );
    @endphp

    <div class="tabs">
        <a href="{{ route('owasp.settings') }}" class="{{ !$group ? 'active' : '' }}">All</a>
        @foreach($groups as $key => $label)
            <a href="{{ route('owasp.settings', ['group' => $key]) }}" class="{{ $group === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if(!$canUpdate)
        <div class="flash">Auditor view — read only. Ask Super Admin to change toggles.</div>
    @endif

    <form method="POST" action="{{ route('owasp.settings.update') }}">
        @csrf
        <div class="panel">
            <table>
                <thead>
                <tr>
                    <th style="width:70px">On/Off</th>
                    <th>Control</th>
                    <th>OWASP Reference</th>
                    <th>Value / Notes</th>
                </tr>
                </thead>
                <tbody>
                @forelse($settings as $i => $item)
                    <tr>
                        <td>
                            <input type="hidden" name="settings[{{ $i }}][key]" value="{{ $item['key'] }}">
                            <input type="hidden" name="settings[{{ $i }}][enabled]" value="0">
                            <label class="switch" title="Toggle {{ $item['label'] }}">
                                <input type="checkbox" name="settings[{{ $i }}][enabled]" value="1" @checked($item['enabled']) @disabled(!$canUpdate)>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>
                            <strong>{{ $item['label'] }}</strong>
                            <div class="muted">{{ $item['description'] }}</div>
                            <div class="muted"><code>{{ $item['key'] }}</code></div>
                        </td>
                        <td class="muted">{{ $item['owasp_ref'] ?? '—' }}</td>
                        <td>
                            @if($item['group'] === 'headers')
                                <input class="value-input" type="text" name="settings[{{ $i }}][value]" value="{{ $item['value'] }}" @disabled(!$canUpdate)>
                            @else
                                <input type="hidden" name="settings[{{ $i }}][value]" value="{{ $item['value'] }}">
                                <span class="muted">Managed via config meta / toggles</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No settings found. Run <code>php artisan owasp:install</code>.</td></tr>
                @endforelse
                </tbody>
            </table>
            @if($canUpdate)
            <div class="row" style="margin-top:1rem">
                <button type="submit" class="btn">Save settings</button>
            </div>
            @endif
        </div>
    </form>
@endsection
