<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('demo_user');
        if (($user['role'] ?? null) !== 'super_admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        return $next($request);
    }
}
