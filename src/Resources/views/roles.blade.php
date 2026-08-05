@extends('owasp-security::layout')

@section('title', 'Roles')

@section('content')
    <h1>Roles & Access</h1>
    <p class="sub">RBAC for OWASP security administration. Super Admin enables audit-driven settings; Auditors are read-only.</p>

    <div class="panel">
        <h2 style="margin-top:0;font-size:1.1rem">Defined roles</h2>
        <table>
            <thead>
            <tr><th>Role</th><th>Description</th><th>Permissions</th></tr>
            </thead>
            <tbody>
            @foreach($roles as $role)
                <tr>
                    <td><strong>{{ $role->label }}</strong><div class="muted">{{ $role->name }}</div></td>
                    <td class="muted">{{ $role->description }}</td>
                    <td>
                        @foreach($role->permissions as $perm)
                            <span class="badge">{{ $perm->name }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2 style="margin-top:0;font-size:1.1rem">Assign role</h2>
        <form method="POST" action="{{ route('owasp.roles.assign') }}" class="row">
            @csrf
            <select name="user_id" required>
                <option value="">Select user</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">#{{ $u->id }} — {{ $u->email }}</option>
                @endforeach
            </select>
            <select name="role" required>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Assign</button>
        </form>
    </div>

    <div class="panel">
        <h2 style="margin-top:0;font-size:1.1rem">Current assignments</h2>
        <table>
            <thead>
            <tr><th>User</th><th>Roles</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($users as $u)
                @php $userRoles = $assignments->get($u->id, collect()); @endphp
                <tr>
                    <td>{{ $u->email }}</td>
                    <td>
                        @forelse($userRoles as $ar)
                            <span class="badge on">{{ $ar->role_label }}</span>
                        @empty
                            <span class="muted">No OWASP role</span>
                        @endforelse
                    </td>
                    <td>
                        @foreach($userRoles as $ar)
                            <form method="POST" action="{{ route('owasp.roles.revoke') }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $u->id }}">
                                <input type="hidden" name="role" value="{{ $ar->role_name }}">
                                <button class="btn danger" type="submit" style="padding:0.25rem 0.55rem;font-size:0.75rem">Revoke {{ $ar->role_name }}</button>
                            </form>
                        @endforeach
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
