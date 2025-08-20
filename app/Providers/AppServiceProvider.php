<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Observers\InvoiceObserver;
use App\Observers\RecurringInvoiceObserver;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
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
        Invoice::observe(InvoiceObserver::class);
        RecurringInvoice::observe(RecurringInvoiceObserver::class);

        FilamentAsset::register([
            Js::make('midtrans-scripts', 'https://app.sandbox.midtrans.com/snap/snap.js')
                ->extraAttributes(['data-client-key' => config('midtrans.client_key')]),
            Js::make('midtrans-payment', asset('js/midtrans-payment.js'))
        ]);
    }
}
