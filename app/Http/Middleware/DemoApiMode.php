<?php

namespace App\Http\Middleware;

use App\Support\DemoDataStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoApiMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('demo.api') !== true) {
            return $next($request);
        }

        $user = DemoDataStore::userForToken($request->bearerToken());
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('demo_user', $user);
        return $next($request);
    }
}
