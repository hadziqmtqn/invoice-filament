<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('invoice:due')
    ->dailyAt('01:00') // Every day at midnight
    ->timezone('Asia/Jakarta') // Set the timezone to Asia/Jakarta
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Invoice due command executed successfully.');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Invoice due command failed.');
    });

\Illuminate\Support\Facades\Schedule::command('invoice:will-due')
    ->dailyAt('02:00') // Every day at midnight
    ->timezone('Asia/Jakarta') // Set the timezone to Asia/Jakarta
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Invoice will-due command executed successfully.');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Invoice will-due command failed.');
    });

\Illuminate\Support\Facades\Schedule::command('invoice:generate-recurring')
    ->dailyAt('10:50') // Every day at midnight
    ->timezone('Asia/Jakarta') // Set the timezone to Asia/Jakarta
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Invoice generate-recurring command executed successfully.');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Invoice generate-recurring command failed.');
    });
