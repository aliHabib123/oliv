<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->login(Login::class)
            ->brandLogo(asset('img/Oliv-Logo-2X-scaled.png'))
            ->brandLogoHeight('3rem')
            ->colors([
                'primary' => '#252621',
                'secondary' => '#D3D2BC',
                'accent' => '#B98B69',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('<style>
                    /* Sidebar styling */
                    .fi-sidebar {
                        background: linear-gradient(180deg, #252621 0%, #2d2e28 100%) !important;
                        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1) !important;
                    }

                    /* Sidebar navigation items */
                    .fi-sidebar-nav-item {
                        margin: 0.25rem 0.75rem !important;
                        border-radius: 0.5rem !important;
                        transition: all 0.2s ease !important;
                    }

                    .fi-sidebar-nav-item:hover {
                        background: rgba(211, 210, 188, 0.1) !important;
                        transform: translateX(4px);
                    }

                    .fi-sidebar-item.fi-active {
                        background: rgba(185, 139, 105, 0.2) !important;
                        border-left: 3px solid #B98B69 !important;
                    }

                    /* Default text - white */
                    .fi-sidebar-item-label,
                    .fi-sidebar span,
                    .fi-sidebar a {
                        color: #ffffff !important;
                    }

                    /* Default icons - white */
                    .fi-sidebar svg {
                        color: #ffffff !important;
                    }

                    /* Hover text - secondary color */
                    .fi-sidebar-item:hover .fi-sidebar-item-btn .fi-sidebar-item-label,
                    .fi-sidebar-item:hover .fi-sidebar-item-label,
                    .fi-sidebar-item:hover span,
                    .fi-sidebar-item:hover a {
                        color: #D3D2BC !important;
                    }

                    /* Hover icons - secondary color */
                    .fi-sidebar-item:hover svg {
                        color: #D3D2BC !important;
                    }

                    /* Active text - secondary color */
                    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label,
                    .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                    .fi-sidebar-item.fi-active span,
                    .fi-sidebar-item.fi-active a {
                        color: #D3D2BC !important;
                        font-weight: 600 !important;
                    }

                    /* Active icons - secondary color */
                    .fi-sidebar-item.fi-active svg {
                        color: #D3D2BC !important;
                    }

                    /* Sidebar groups */
                    .fi-sidebar-group-label {
                        color: #ffffff !important;
                        font-size: 0.75rem !important;
                        text-transform: uppercase !important;
                        letter-spacing: 0.05em !important;
                        font-weight: 600 !important;
                        padding: 1rem 0 0.5rem 0 !important;
                        opacity: 0.9 !important;
                    }

                    /* Logo area */
                    .fi-sidebar-header {
                        padding: 1.5rem 1rem !important;
                        border-bottom: 1px solid rgba(211, 210, 188, 0.1) !important;
                    }

                    /* User menu */
                    .fi-sidebar-footer {
                        border-top: 1px solid rgba(211, 210, 188, 0.1) !important;
                        padding: 1rem !important;
                    }

                    /* Add padding to main content area for footer space */
                    .fi-main,
                    .fi-body {
                    }
                </style>')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('<div style="width: 100%; background: white; border-top: 1px solid #e5e7eb;">
                    <div style="text-align: center; padding: 1rem 0; font-size: 0.875rem; color: #6b7280;">
                        © {{ date("Y") }} Powered by <a href="https://oliv.tech" target="_blank" style="color: #252621; text-decoration: none;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">Oliv.tech</a>
                    </div>
                </div>')
            );
    }
}
