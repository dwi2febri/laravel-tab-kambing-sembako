<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Auth\Pages\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->renderHook(PanelsRenderHook::SIMPLE_PAGE_START, fn (): View => view('filament.login-art'), scopes: Login::class)
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn (): View => view('filament.login-footer'), scopes: Login::class)
            ->brandName('SAKINA')
            ->brandLogo(asset('images/sakina-logo.webp'))
            ->brandLogoHeight('3.5rem')
            ->favicon(asset('images/icon.svg'))
            ->viteTheme('resources/css/admin.css')
            ->sidebarFullyCollapsibleOnDesktop()
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): View => view('filament.notification-bell'))
            ->navigationItems([
                NavigationItem::make('Landing Page')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (): string => route('home'))
                    ->sort(1),
                NavigationItem::make('Halaman Katalog')
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn (): string => route('catalog'))
                    ->sort(2),
            ])
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
