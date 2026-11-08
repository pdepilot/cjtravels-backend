<?php

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::checkAdmin()) {
            Session::set('url.intended', $request->uri());
            Session::flash('error', 'Please sign in to access the administrator console.');
            return Response::redirect(route('admin.login'));
        }

        return $next($request);
    }
}

