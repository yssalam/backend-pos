<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $role = $request->user()?->role;

        if (!in_array($role, ['admin', 'super_admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }


        return $next($request);
    }
}
