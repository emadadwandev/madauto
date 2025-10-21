<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect super admins to super admin dashboard
        if ($request->user()->isSuperAdmin()) {
            return redirect()->intended(route('super-admin.dashboard', absolute: false));
        }

        // Redirect tenant users to tenant dashboard (use path since we're already on tenant subdomain)
        return redirect()->intended('/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $wasSuperAdmin = Auth::user()?->isSuperAdmin() ?? false;

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Redirect to appropriate login page based on subdomain
        $host = $request->getHost();
        $adminSubdomain = config('app.admin_subdomain', 'admin');
        $domain = config('app.domain', 'localhost');

        if (str_contains($host, "{$adminSubdomain}.{$domain}")) {
            return redirect('/login'); // Will stay on admin subdomain
        }

        return redirect('/');
    }
}
