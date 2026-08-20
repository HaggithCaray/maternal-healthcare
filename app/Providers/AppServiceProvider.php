<?php

namespace App\Providers;

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
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Gate::policy(\App\Models\Patient::class, \App\Policies\PatientPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\MaternalRecord::class, \App\Policies\MaternalRecordPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\ChildRecord::class, \App\Policies\ChildRecordPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Immunization::class, \App\Policies\ImmunizationPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\ChatMessage::class, \App\Policies\ChatMessagePolicy::class);
    }
}
