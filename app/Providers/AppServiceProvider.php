<?php

namespace App\Providers;

use App\Http\Responses\AdminLoginResponse;
use App\Models\User;
use App\Services\SavingService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LoginResponse::class, AdminLoginResponse::class);
    }

    public function boot(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->role === 'admin' && $user->is_active);
        Carbon::setLocale('id');
        Event::listen(Login::class, function (Login $event): void {
            SavingService::log($event->user, 'auth.login', 'Masuk aplikasi');
        });
    }
}
