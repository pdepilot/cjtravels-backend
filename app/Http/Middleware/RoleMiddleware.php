<?php

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $admin = Auth::admin();
        if (!$admin) {
            return Response::redirect(route('admin.login'));
        }

        if ($requiredRole && !$admin->hasRole($requiredRole)) {
            Session::flash('error', "Access denied. The '{$requiredRole}' role is required for this action.");
            return Response::redirect(route('admin.dashboard'));
        }

        return $next($request);
    }
}

