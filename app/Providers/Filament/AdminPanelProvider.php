<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            ->brandName('Daser Restaurant OS')
            ->favicon(fn () => \App\Modules\Settings\Models\CompanySetting::first()?->company_favicon ? asset('storage/' . \App\Modules\Settings\Models\CompanySetting::first()->company_favicon) : asset('favicon.png'))
            ->renderHook(
                'panels::sidebar.footer',
                fn (): string => view('filament.sidebar-footer')->render(),
            )
            ->renderHook(
                'panels::styles.after',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-compact-sidebar.css?v=' . (file_exists(public_path('css/filament-compact-sidebar.css')) ? filemtime(public_path('css/filament-compact-sidebar.css')) : '1.0.0')) . '">',
            )
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->collapsibleNavigationGroups(false)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->renderHook(
                'panels::body.end',
                fn (): string => <<<'HTML'
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const findAndStyleDashboard = () => {
                                const labels = document.querySelectorAll('.fi-sidebar-item-label, span.font-medium'); // Try broad selectors
                                for (const label of labels) {
                                    if (label.textContent.trim() === 'Dashboard') {
                                        const item = label.closest('li.fi-sidebar-item');
                                        if (item) {
                                            item.style.borderBottom = '1px solid #e5e7eb'; // Gray-200
                                            item.style.marginBottom = '0.35rem';
                                            item.style.paddingBottom = '0.35rem';
                                            
                                            // Check for dark mode
                                            if (document.documentElement.classList.contains('dark')) {
                                                item.style.borderBottomColor = '#374151'; // Gray-700
                                            }
                                            return;
                                        }
                                    }
                                }
                            };
                            
                            // Run immediately and after specific Filament events if needed
                            findAndStyleDashboard();
                            
                            // Observer specifically for Filament's dynamic loading if it uses Livewire navigation
                            new MutationObserver(findAndStyleDashboard).observe(document.body, { childList: true, subtree: true });
                        });
                    </script>
                HTML,
            )
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
            ]);
    }
}
