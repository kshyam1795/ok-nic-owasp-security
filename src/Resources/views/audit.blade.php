@extends('owasp-security::layout')

@section('title', 'Audit Logs')

@section('content')
    <h1>Security Audit Logs</h1>
    <p class="sub">Immutable trail of setting changes, auth events, OTP, and role assignments for auditor review.</p>

    <div class="panel">
        <table>
            <thead>
            <tr>
                <th>When</th>
                <th>Event</th>
                <th>Category</th>
                <th>Description</th>
                <th>IP</th>
                <th>User</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="muted">{{ $log->created_at }}</td>
                    <td><code>{{ $log->event }}</code></td>
                    <td><span class="badge">{{ $log->category }}</span></td>
                    <td>{{ $log->description }}</td>
                    <td class="muted">{{ $log->ip_address }}</td>
                    <td class="muted">{{ $log->user_id ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No audit events yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem">{{ $logs->links() }}</div>
    </div>
@endsection
