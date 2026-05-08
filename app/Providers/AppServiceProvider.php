<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        User::observe(\App\Observers\UserObserver::class);

        // {user} resolverá estrictamente por public_id (ULID).
        // No se permite ID numérico ni withTrashed() para prevenir
        // enumeración y operaciones sobre usuarios soft-deleted.
        Route::bind('user', function ($value) {
            return User::where('public_id', $value)->firstOrFail();
        });

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->user('api')?->id ?: $request->ip());
        });
    }
}
