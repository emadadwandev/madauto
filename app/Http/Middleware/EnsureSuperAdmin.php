<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Check if user is currently impersonating
        if (session()->has('impersonating_from')) {
            // Get the original super admin user who started the impersonation
            $originalUserId = session('impersonating_from');
            $originalUser = User::find($originalUserId);

            // Allow access if the original user is a super admin
            if ($originalUser && $originalUser->isSuperAdmin()) {
                return $next($request);
            }
        }

        // Normal check for current user
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized. Super admin access required.');
        }

        return $next($request);
    }
}
