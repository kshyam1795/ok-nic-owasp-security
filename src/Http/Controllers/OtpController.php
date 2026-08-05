<?php

namespace Growats\OkNicOwaspSecurity\Http\Controllers;

use Growats\OkNicOwaspSecurity\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function __construct(
        protected OtpService $otp
    ) {}

    public function challenge(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->guest(route('login'));
        }

        return view('owasp-security::otp-challenge');
    }

    public function send(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $payload = $this->otp->generate($user, 'login', 'email');

        // Best-effort email delivery; host apps can listen and send via their mailer
        try {
            if (config('mail.default')) {
                Mail::raw(
                    "Your OWASP security OTP is: {$payload['code']}\nIt expires at {$payload['expires_at']}.",
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Your security verification code');
                    }
                );
            }
        } catch (\Throwable) {
            // Code still available via event / logs in local for debugging
        }

        event(new \Growats\OkNicOwaspSecurity\Events\OtpGenerated($user, $payload));

        if (app()->environment('local') || config('app.debug')) {
            session()->flash('otp_debug', $payload['code']);
        }

        return back()->with('success', 'OTP sent. Check your email.');
    }

    public function verify(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'code' => 'required|string|min:4|max:12',
        ]);

        if (!$this->otp->verify($user, $data['code'], 'login')) {
            return back()->withErrors(['code' => 'Invalid or expired OTP.']);
        }

        return redirect()->intended(route('owasp.dashboard'))->with('success', 'OTP verified.');
    }
}
