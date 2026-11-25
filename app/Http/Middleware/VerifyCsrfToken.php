<?php

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

class VerifyCsrfToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only verify state-modifying requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
            $sessionToken = Session::token();

            if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
                Session::flash('error', 'Your session token expired or is invalid. Please try submitting again.');
                if (self::expectsJson($request)) {
                    return Response::json([
                        'success' => false,
                        'error' => 'Your session has expired. Refresh the page and submit the enquiry again.',
                    ], 419);
                }
                return Response::redirect($request->uri());
            }
        }

        return $next($request);
    }

    protected static function expectsJson(Request $request): bool
    {
        $uri = $request->uri();
        if (str_contains($uri, '/api/')) {
            return true;
        }

        $accept = (string)$request->header('Accept', '');
        if (str_contains(strtolower($accept), 'application/json')) {
            return true;
        }

        return strcasecmp((string)$request->header('X-Requested-With', ''), 'XMLHttpRequest') === 0;
    }
}

