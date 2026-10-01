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

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('account:' . Str::lower((string) $request->input('email')) . '|' . $request->ip()),
            // One address trying a password against many accounts.
            Limit::perMinute(20)->by('ip:' . $request->ip()),
        ]);

        // The current-password check would otherwise let a stolen session guess the real password.
        RateLimiter::for('password-change', fn (Request $request) => Limit::perMinute(5)->by((string) $request->user()?->id));

        // Only sending is limited; opening the chat is not.
        RateLimiter::for('chat', function (Request $request) {
            if (! $request->isMethod('post')) {
                return Limit::none();
            }

            $id = (string) $request->user()?->id;

            return array_values(array_filter([
                Limit::perMinute(20)->by("messages:{$id}"),
                // Attachments can be 25 MB each; cap how much one account can upload.
                $request->hasFile('file') ? Limit::perHour(30)->by("uploads:{$id}") : null,
            ]));
        });

        RateLimiter::for('sms', fn (Request $request) => $request->isMethod('post')
            ? Limit::perMinute(10)->by((string) $request->user()?->id)
            : Limit::none());
    }
}
