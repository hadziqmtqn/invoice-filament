<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Observers\InvoiceObserver;
use App\Observers\InvoicePaymentObserver;
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
        InvoicePayment::observe(InvoicePaymentObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }
}
