<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class DemoRouteRegistrar
{
    public function __construct()
    {
        if (!config('demo.api')) {
            return;
        }

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/demo-api.php'));
    }
}
