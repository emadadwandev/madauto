<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log key information about the request
        Log::info('Authentication Debug', [
            'url' => $request->url(),
            'host' => $request->getHost(),
            'session_id' => session()->getId(),
            'csrf_token' => csrf_token(),
            'user_id' => auth()->id(),
            'is_authenticated' => auth()->check(),
            'route_name' => $request->route()?->getName(),
            'method' => $request->method(),
            'session_domain' => config('session.domain'),
            'intended_url' => session('url.intended'),
            'session_data' => [
                'previous_url' => session('_previous.url'),
                'flash_messages' => session()->all(),
            ],
        ]);

        $response = $next($request);

        // Log response information including any additional debugging
        Log::info('Authentication Debug Response', [
            'status' => $response->getStatusCode(),
            'is_redirect' => $response->isRedirection(),
            'redirect_url' => $response->isRedirection() ? $response->headers->get('Location') : null,
            'user_after_request' => auth()->id(),
            'authenticated_after' => auth()->check(),
        ]);

        return $response;
    }
}
