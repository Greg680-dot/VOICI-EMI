<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class GuestQuotaMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            $ip = $request->ip();
            $cacheKey = 'guest_fc_quota:' . $ip;

            $attempts = Cache::get($cacheKey, 0);

            if ($attempts >= 3) {
                return response()->json([
                    'message' => 'Quota de vérification d\'urgence dépassé pour les invités (maximum 3 par jour). Veuillez créer un compte pour utiliser le service sans limite.'
                ], 429);
            }

            Cache::put($cacheKey, $attempts + 1, now()->addDay());
        }

        return $next($request);
    }
}
