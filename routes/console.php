<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('invoice:due')
    ->dailyAt('07:50') // Every day at midnight
    ->timezone('Asia/Jakarta') // Set the timezone to Asia/Jakarta
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Invoice due command executed successfully.');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Invoice due command failed.');
    })
    ->sendOutputTo(storage_path('logs/invoice_due.log'));
