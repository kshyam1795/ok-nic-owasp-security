@extends('owasp-security::layout')

@section('title', 'Dashboard')

@section('content')
    <h1>Security Control Center</h1>
    <p class="sub">Super Admin enables or disables OWASP controls based on auditor findings. Changes apply at runtime.</p>

    <div class="cards">
        <div class="card"><div class="n">{{ $stats['total'] }}</div><div class="l">Total controls</div></div>
        <div class="card"><div class="n" style="color:var(--ok)">{{ $stats['enabled'] }}</div><div class="l">Enabled</div></div>
        <div class="card"><div class="n" style="color:var(--danger)">{{ $stats['disabled'] }}</div><div class="l">Disabled</div></div>
        <div class="card"><div class="n">{{ $stats['headers'] }}</div><div class="l">Header controls</div></div>
        <div class="card"><div class="n">{{ $stats['authentication'] }}</div><div class="l">Auth controls</div></div>
        <div class="card"><div class="n">{{ $stats['protections'] }}</div><div class="l">Protections</div></div>
    </div>

    <div class="panel">
        <h2 style="margin-top:0;font-size:1.1rem">Quick actions</h2>
        <div class="row">
            <a class="btn" href="{{ route('owasp.settings', ['group' => 'headers']) }}">Manage Headers</a>
            <a class="btn secondary" href="{{ route('owasp.settings', ['group' => 'authentication']) }}">Auth & OTP</a>
            <a class="btn secondary" href="{{ route('owasp.roles') }}">Assign Roles</a>
            <a class="btn secondary" href="{{ route('owasp.audit') }}">View Audit Trail</a>
        </div>
    </div>

    <div class="panel">
        <h2 style="margin-top:0;font-size:1.1rem">Control snapshot</h2>
        <table>
            <thead>
            <tr>
                <th>Control</th>
                <th>Group</th>
                <th>OWASP</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach($all as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td><span class="badge">{{ $item['group'] }}</span></td>
                    <td class="muted">{{ $item['owasp_ref'] }}</td>
                    <td>
                        @if($item['enabled'])
                            <span class="badge on">Enabled</span>
                        @else
                            <span class="badge off">Disabled</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
