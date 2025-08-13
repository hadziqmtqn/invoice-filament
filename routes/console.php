<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

return function (\Illuminate\Console\Scheduling\Schedule $schedule) {
    $schedule->command('invoice:due')
        ->dailyAt('01:00')
        ->timezone('Asia/Jakarta')
        ->withoutOverlapping()
        ->onSuccess(fn() => Log::info('Invoice due command executed successfully.'))
        ->onFailure(fn() => Log::error('Invoice due command failed.'));

    $schedule->command('invoice:will-due')
        ->dailyAt('02:00')
        ->timezone('Asia/Jakarta')
        ->withoutOverlapping()
        ->onSuccess(fn() => Log::info('Invoice will-due command executed successfully.'))
        ->onFailure(fn() => Log::error('Invoice will-due command failed.'));

    $schedule->command('invoice:generate-recurring')
        ->everyMinute()
        ->timezone('Asia/Jakarta')
        ->withoutOverlapping()
        ->onSuccess(fn() => Log::info('Invoice generate-recurring command executed successfully.'))
        ->onFailure(fn() => Log::error('Invoice generate-recurring command failed.'));
};
