<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\PaymentChart;
use App\Filament\Widgets\PaymentMethodChart;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Application;
use Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Filament\Livewire\Notifications;
use Filament\Support\Enums\Alignment;

class PanelPanelProvider extends PanelProvider
{
    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        $application = null;
        if (Schema::hasTable('applications')) {
            $application = Application::first();
        }

        Notifications::alignment(Alignment::Center);

        return $panel
            ->default()
            ->id('panel')
            ->path('panel')
            ->spa()
            ->login() // alt: App/Filament/Pages/Login.php
            ->brandName($application?->name)
            ->favicon($application?->favicon)
            ->colors([
                'primary' => Color::Teal,
            ])
            ->font('poppins')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->passwordReset()
            ->emailVerification()
            ->collapsibleNavigationGroups(false)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            //->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                PaymentChart::class,
                PaymentMethodChart::class
            ])
            ->navigationGroups([
                'Main',
                'Finance',
                'Payments',
                'References',
                'Configuration',
                'Settings',
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
            ->authGuard('web')
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentApexChartsPlugin::make(),
                BreezyCore::make()
                    ->avatarUploadComponent(fn() => FileUpload::make('profile_photo_path')->hiddenLabel()->directory('avatar')->disk('public')->avatar())
                    ->enableTwoFactorAuthentication()
                    ->myProfile(true, // Sets the 'account' link in the panel User Menu (default = true)
                        'My Profile', // Customizes the 'account' link label in the panel User Menu (default = null)
                        false, // Adds a main navigation item for the My Profile page (default = false)
                        true, // Sets the navigation group for the My Profile page (default = null)
                        'my-profile', // Enables the avatar upload form component (default = false)
                        'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    )
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
