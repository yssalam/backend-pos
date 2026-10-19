<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->attributes->get('demo_user')['role'] ?? null;
        if (!in_array($role, ['admin', 'super_admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        return $next($request);
    }
}
