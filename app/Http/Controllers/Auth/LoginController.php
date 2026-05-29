<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Horsefly\LoginDetail;
use Illuminate\Support\Carbon;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    protected function authenticated($request, $user)
    {
        return redirect()->to(PermissionHelper::firstAllowedRoute($user));
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->route('login')->withErrors($validator)->withInput();
        }

        // ── IP address check ──────────────────────────────────────────────────
        // A user may log in when their IP is:
        //   (a) registered exactly in the ip_addresses table (status=1), OR
        //   (b) covered by a prefix in the allowed_ips table (first 3 octets)
        //
        // This dual-check lets Docker / NAT / VPN users access the system via
        // the allowed_ips prefix list, while still supporting the per-device
        // exact-match whitelist for stricter environments.
        $clientIp = $request->ip();
        Log::info('Login attempt from IP: ' . $clientIp);

        if (!$this->isIpAllowed($clientIp)) {
            Log::warning('Blocked login attempt from unregistered IP: ' . $clientIp);
            return redirect()->route('login')
                ->withErrors(['ip' => 'Your IP address (' . $clientIp . ') is not registered. Contact your administrator.']);
        }

        // ── Rate limiting ─────────────────────────────────────────────────────
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // ── Authenticate ──────────────────────────────────────────────────────
        if (Auth::attempt($credentials, $request->has('remember'))) {
            $user = Auth::user();

            if ($user->is_active == 1) {
                // Record login (firstOrCreate avoids race-condition duplicate inserts)
                $loginDetail = LoginDetail::firstOrCreate(
                    [
                        'user_id'    => $user->id,
                        'ip_address' => $clientIp,
                    ],
                    [
                        'login_at' => Carbon::now(),
                    ]
                );
                if (!$loginDetail->wasRecentlyCreated) {
                    $loginDetail->logout_at = null;
                    $loginDetail->save();
                }

                $request->session()->regenerate();

                return redirect()->to(PermissionHelper::firstAllowedRoute($user));
            }

            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not active. Please contact support.']);
        }

        $this->incrementLoginAttempts($request);

        return redirect()->route('login')
            ->withErrors(['email' => 'Invalid credentials. Please try again.'])
            ->withInput();
    }

    /**
     * Check whether the given IP is allowed to log in.
     *
     * SKIP_IP_CHECK=true in .env bypasses all IP checks — useful for first-time
     * VPS setup before IP whitelists have been configured.  Disable once the
     * admin has logged in and added trusted IPs via the admin panel.
     *
     * Two-tier check (when enabled):
     *   1. Exact match  — ip_addresses table (status = 1)
     *   2. Prefix match — allowed_ips table  (first-3-octet subnet)
     */
    protected function isIpAllowed(string $ip): bool
    {
        // Bypass for fresh deployments / development environments.
        // Must use config() — env() returns null when config:cache is active.
        if (config('app.skip_ip_check', false)) {
            return true;
        }

        // Tier 1: exact match
        $exact = DB::table('ip_addresses')
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->where('ip_address', $ip)
            ->exists();

        if ($exact) {
            return true;
        }

        // Tier 2: prefix/subnet match (e.g. 192.168.1 covers .1–.254)
        $parts  = explode('.', $ip);
        $prefix = implode('.', array_slice($parts, 0, 3));

        return DB::table('allowed_ips')
            ->where('ip_prefix', $prefix)
            ->exists();
    }

    // ── Rate-limit helpers ────────────────────────────────────────────────────

    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        // 5 attempts allowed; 15-minute lockout.
        return RateLimiter::tooManyAttempts($this->throttleKey($request), 5, 15);
    }

    protected function throttleKey(Request $request): string
    {
        // Key on email + IP so brute-forcing from multiple IPs is also throttled.
        return 'login|' . strtolower($request->input('email')) . '|' . $request->ip();
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), 60 * 15);
    }

    protected function fireLockoutEvent(Request $request): void {}

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        return redirect()->route('login')
            ->withErrors(['email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).']);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout()
    {
        $user = Auth::user();

        if ($user) {
            LoginDetail::where('user_id', $user->id)
                ->whereNull('logout_at')
                ->update(['logout_at' => Carbon::now()]);
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login')
            ->with('message', 'You have been logged out successfully.');
    }
}
