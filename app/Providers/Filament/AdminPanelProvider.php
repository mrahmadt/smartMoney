<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Enums\Width;
use Filament\Pages\Enums\SubNavigationPosition;
// use Filament\Pages\Dashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SpendingChart;
use App\Filament\Widgets\SpendingCategoriesChart;
use App\Filament\Widgets\TopCategories;
use App\Filament\Widgets\TopTransactions;
use App\Filament\Widgets\TopMerchants;
use App\Filament\Widgets\RecentTransactions;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->multiFactorAuthentication(
                providers: [
                    AppAuthentication::make()->recoverable(true),
                ],
                isRequired: fn() => auth()->user()?->mfa_required ?? false,
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverview::class,
                RecentTransactions::class,
                TopTransactions::class,
                TopMerchants::class,
                TopCategories::class,
                // SpendingChart::class,
                SpendingCategoriesChart::class,
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
            ->subNavigationPosition(SubNavigationPosition::Top)
            ->maxContentWidth(Width::Full)
            ->globalSearch(false)
            // ->topNavigation()
            ->topNavigation(false)
            ->renderHook(
                'panels::head.end',
                fn() => '<script src="/js/webpush.js"></script><style>.fi-page-content {row-gap: calc(var(--spacing) * 0.1) !important;} .fi-page-header-main-ctn { padding-top: 1px !important; padding-bottom: 0 !important; }</style>'
            )
            ->renderHook(
                'panels::body.end',
                function () {
                    $user = auth()->user();
                    if (!$user) {
                        return '';
                    }
                    $userData = json_encode([
                        'userId' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]);
                    return <<<HTML
                    <script>
                    //     alert('userAgent: ' + navigator.userAgent);
                    //     if (!window._iOSLoginSent && navigator.userAgent.includes('iOSApp') ) {
                    //     // window._iOSLoginSent = true;
                    //     alert('Fake');
                    // }
                    if (!window._iOSLoginSent && navigator.userAgent.includes('iOSApp') && window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.userLogin) {
                        window._iOSLoginSent = true;
                        alert('Sending login data to iOS app');
                        window.webkit.messageHandlers.userLogin.postMessage({$userData});
                    }
                    </script>
                    HTML;
                }
            )
        ;
    }
}
