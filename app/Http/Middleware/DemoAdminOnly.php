<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (($request->attributes->get('demo_user')['role'] ?? null) !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        return $next($request);
    }
}
