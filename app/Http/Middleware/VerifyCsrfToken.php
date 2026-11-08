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
                return Response::redirect($request->uri());
            }
        }

        return $next($request);
    }
}

