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
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
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
            ->brandName('YottoCode')
            ->brandLogo(asset('img/yottocode-logo.svg'))
            ->favicon(asset('img/yottocode-favicon.png'))
            ->brandLogoHeight('2rem')
            ->font('Inter')
            ->colors([
                'primary' => '#0f1626',
                'secondary' => '#b3e6ff',
                'accent' => '#2aabee',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
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
                    /* Override default color palette with custom color */
                    .fi-bg-color-400,
                    [style*="background-color: #aaba3a"],
                    [style*="background: #aaba3a"] {
                        background-color: #186288 !important;
                        --bg: #186288 !important;
                    }

                    .fi-border-color-400,
                    [style*="border-color: #aaba3a"] {
                        border-color: #186288 !important;
                        --border-color: #186288 !important;
                    }

                    .fi-text-color-400,
                    [style*="color: #aaba3a"] {
                        color: #186288 !important;
                        --text-color: #186288 !important;
                    }

                    /* Override focus rings and outlines */
                    *:focus,
                    *:focus-visible {
                        outline-color: #186288 !important;
                        border-color: #186288 !important;
                    }

                    /* Button states */
                    .fi-btn[style*="#aaba3a"],
                    button[style*="#aaba3a"] {
                        background-color: #186288 !important;
                        border-color: #186288 !important;
                        color: #ffffff !important;
                    }

                    /* Ensure all button text is white */
                    .fi-btn,
                    .fi-btn span,
                    .fi-btn-label,
                    button.fi-btn {
                        color: #ffffff;
                    }

                    /* Primary buttons */
                    .fi-btn-primary,
                    .fi-btn-primary span {
                        color: #ffffff;
                    }
                    .fi-fo-builder-add-between-items-ctn button.fi-btn,
                    .fi-fo-repeater-add button.fi-btn,
                    .fi-dropdown.fi-fo-builder-block-picker button.fi-btn{
                        color: #000;
                    }
                    /* Sidebar styling */
                    .fi-sidebar {
                        background: linear-gradient(180deg, #0f1626 0%, #16203a 100%) !important;
                        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1) !important;
                    }

                    /* Sidebar navigation items */
                    .fi-sidebar-nav-item {
                        margin: 0.25rem 0.75rem !important;
                        border-radius: 0.5rem !important;
                        transition: all 0.2s ease !important;
                    }

                    .fi-sidebar-nav-item:hover {
                        background: rgba(179, 230, 255, 0.1) !important;
                        transform: translateX(4px);
                    }

                    .fi-sidebar-item.fi-active {
                        background: rgba(42, 171, 238, 0.2) !important;
                        // border-left: 3px solid #2aabee !important;
                    }

                    /* Default text - white */
                    .fi-sidebar-item .fi-sidebar-item-label,
                    .fi-sidebar-item .fi-sidebar span,
                    .fi-sidebar-item .fi-sidebar a,
                    .fi-sidebar-item svg {
                        color: #ffffff ;
                    }


                    /* Hover text - secondary color */
                    .fi-sidebar-item:hover .fi-sidebar-item-label,
                    .fi-sidebar-item:hover .fi-sidebar span,
                    .fi-sidebar-item:hover svg {
                       color: black;
                    }

                    /* Active text - secondary color */
                    .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                    .fi-sidebar-item.fi-active .fi-sidebar span,
                    .fi-sidebar-item.fi-active .fi-sidebar a,
                    .fi-sidebar-item.fi-active svg {
                        color: black;
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

                    /* Logo area: white logo on the dark sidebar */
                    .fi-sidebar-header img.fi-logo {
                        content: url("'.asset('img/yottocode-logo-white.svg').'");
                    }

                    .fi-sidebar-header {
                        padding: 1.5rem 1rem !important;
                        border-bottom: 1px solid rgba(179, 230, 255, 0.1) !important;
                    }

                    /* User menu */
                    .fi-sidebar-footer {
                        border-top: 1px solid rgba(179, 230, 255, 0.1) !important;
                        padding: 1rem !important;
                    }

                    /* Sidebar footer copyright */
                    .sidebar-copyright {
                        text-align: center;
                        padding: 1rem 0;
                        font-size: 0.75rem;
                        color: rgba(255, 255, 255, 0.7);
                        border-top: 1px solid rgba(179, 230, 255, 0.1);
                        margin-top: auto;
                    }

                    .sidebar-copyright a {
                        color: #b3e6ff;
                        text-decoration: none;
                    }

                    .sidebar-copyright a:hover {
                        text-decoration: underline;
                    }
                </style>')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => Blade::render('<div class="sidebar-copyright">
                    &copy; {{ date("Y") }} Powered by <a href="https://yottocode.com" target="_blank">YottoCode</a>
                </div>')
            );
    }
}
