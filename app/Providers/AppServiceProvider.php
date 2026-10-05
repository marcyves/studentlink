<?php

namespace App\Providers;

use App\Listeners\RecordLoginAttempt;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        Event::listen(Login::class, [RecordLoginAttempt::class, 'successful']);
        Event::listen(Failed::class, [RecordLoginAttempt::class, 'failed']);
        Event::listen(Lockout::class, [RecordLoginAttempt::class, 'lockedOut']);
    }
}
