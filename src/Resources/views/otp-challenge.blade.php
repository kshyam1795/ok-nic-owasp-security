@extends('owasp-security::layout')

@section('title', 'OTP Verification')

@section('content')
    <h1>OTP Verification</h1>
    <p class="sub">Multi-factor step for privileged roles (Super Admin, Security Admin, Auditor) when OTP protection is enabled.</p>

    <div class="panel" style="max-width:480px">
        <form method="POST" action="{{ route('owasp.otp.send') }}" style="margin-bottom:1rem">
            @csrf
            <button class="btn secondary" type="submit">Send OTP to my email</button>
        </form>

        @if(session('otp_debug'))
            <div class="flash">Local debug OTP: <strong>{{ session('otp_debug') }}</strong></div>
        @endif

        <form method="POST" action="{{ route('owasp.otp.verify') }}">
            @csrf
            <label class="muted" for="code">Enter verification code</label>
            <div class="row" style="margin-top:0.5rem">
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required placeholder="6-digit code">
                <button class="btn" type="submit">Verify</button>
            </div>
        </form>
    </div>
@endsection
