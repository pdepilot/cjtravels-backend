<?php

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use Closure;

class AdminGuestMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::checkAdmin()) {
            return Response::redirect(route('admin.dashboard'));
        }

        return $next($request);
    }
}

