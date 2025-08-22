<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ApplicationResource;
use App\Filament\Resources\BankAccountResource;
use App\Filament\Resources\BankResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\MessageTemplateCategoryResource;
use App\Filament\Resources\MessageTemplateResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PaymentSummaryResource;
use App\Filament\Resources\RecurringInvoiceResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\WhatsappConfigResource;
use App\Filament\Widgets\PaymentChart;
use App\Filament\Widgets\PaymentMethodChart;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Application;
use BezhanSalleh\FilamentShield\Resources\RoleResource;
use Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        if (!App::runningInConsole() || (App::runningInConsole() && !in_array(request()->server('argv')[1] ?? null, ['migrate', 'db:seed', 'config:cache', 'config:clear', 'migrate:fresh', 'migrate:refresh', 'migrate:install']))) {
            if (Schema::hasTable('applications')) {
                $application = Application::first();
            }
        }

        Notifications::alignment(Alignment::Center);

        return $panel
            ->default()
            ->id('panel')
            ->path('panel')
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
            /*->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop(false)*/
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
            ->navigation(function (NavigationBuilder $navigationBuilder): NavigationBuilder {
                return $navigationBuilder
                    ->items([
                        ...Dashboard::getNavigationItems(),
                        ...$this->filterResourceNavigationItems(UserResource::class),
                        ...$this->filterResourceNavigationItems(RoleResource::class),
                    ])
                    ->groups([
                        NavigationGroup::make('Finance')
                            ->label('Finance')
                            ->icon('heroicon-o-receipt-percent')
                            ->items([
                                ...$this->filterResourceNavigationItems(InvoiceResource::class),
                                ...$this->filterResourceNavigationItems(RecurringInvoiceResource::class),
                                ...$this->filterResourceNavigationItems(PaymentResource::class),
                                ...$this->filterResourceNavigationItems(PaymentSummaryResource::class),
                            ]),
                        NavigationGroup::make('Reference')
                            ->label('Reference')
                            ->icon('heroicon-o-cube')
                            ->items([
                                ...$this->filterResourceNavigationItems(ItemResource::class),
                                ...$this->filterResourceNavigationItems(BankResource::class),
                                ...$this->filterResourceNavigationItems(BankAccountResource::class),
                            ]),
                        NavigationGroup::make('Configuration')
                            ->label('Configuration')
                            ->icon('heroicon-o-cpu-chip')
                            ->items([
                                ...$this->filterResourceNavigationItems(WhatsappConfigResource::class),
                                ...$this->filterResourceNavigationItems(MessageTemplateCategoryResource::class),
                                ...$this->filterResourceNavigationItems(MessageTemplateResource::class),
                            ]),
                    ]);
            })
            ->userMenuItems([
                MenuItem::make()
                    ->label('Settings')
                    ->url(fn (): string => ApplicationResource::getUrl('edit', ['record' => $application?->slug]))
                    ->icon('heroicon-o-cog-6-tooth'),
                // ...
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

    function filterResourceNavigationItems($resource) {
        // Buang namespace model menjadi dan pisahkan dengan ::, misal model InvoicePayment menjadi invoice::payment
        $permission = 'view_any_' . str_replace('_', '::', Str::snake(class_basename($resource::getModel())));

        if (Gate::allows($permission)) {
            return $resource::getNavigationItems();
        }

        return [];
    }
}
