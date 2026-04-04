<?php

namespace App\Providers;

use App\Listeners\DeregisterSession;
use App\Listeners\EnforceSessionLimit;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configurePulseAccess();
        $this->configureSessionEvents();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Trust Cloudflare proxies so request()->ip() returns the real visitor IP
        Request::setTrustedProxies(
            ['REMOTE_ADDR'],
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
        );

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureSessionEvents(): void
    {
        Event::listen(Login::class, EnforceSessionLimit::class);
        Event::listen(Logout::class, DeregisterSession::class);
    }

    protected function configurePulseAccess(): void
    {
        Gate::define('viewPulse', function ($user): bool {
            return ! app()->isProduction() || $user->email === 'naifmhd@gmail.com';
        });

        // Gate::define('viewHorizon', function ($user = null) {
        //      return ! app()->isProduction() || $user->email === 'naifmhd@gmail.com';
        //     return in_array(optional($user)->email, [
        //         'naifmhd@gmail.com'
        //     ]);
        // });

    }
}
