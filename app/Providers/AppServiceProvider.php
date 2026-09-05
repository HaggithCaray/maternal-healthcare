<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }

        Gate::policy(\App\Models\Patient::class, \App\Policies\PatientPolicy::class);
        Gate::policy(\App\Models\MaternalRecord::class, \App\Policies\MaternalRecordPolicy::class);
        Gate::policy(\App\Models\ChildRecord::class, \App\Policies\ChildRecordPolicy::class);
        Gate::policy(\App\Models\Immunization::class, \App\Policies\ImmunizationPolicy::class);
        Gate::policy(\App\Models\ChatMessage::class, \App\Policies\ChatMessagePolicy::class);

        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('email')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key);
        });
    }
}
