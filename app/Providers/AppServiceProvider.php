<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('vehicle-lookup', function (Request $request) {
            return Limit::perMinute(3)->by((string) $request->user()->id)
                ->response(fn ($request, $headers) => response()->json([
                    'code' => 'lookup_rate_limited',
                    'message' => 'Limite de três tentativas por minuto atingido. Aguarde ou preencha manualmente.',
                ], 429, $headers));
        });
    }
}
