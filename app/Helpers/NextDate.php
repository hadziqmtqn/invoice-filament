<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class NextDate
{
    public static function calculateNextDate($date, $frequency, $repeatEvery): Carbon
    {
        $date = Carbon::parse($date)->copy(); // Carbon instance, JANGAN pakai reference!
        $now = now();

        $repeatEvery = (int)($repeatEvery ?: 1);

        // Cek jika next interval sudah lewat, tambahkan terus sampai lewat now
        while ($date <= $now) {
            $date = match ($frequency) {
                'seconds' => $date->addSeconds($repeatEvery),
                'minutes' => $date->addMinutes($repeatEvery),
                'days' => $date->addDays($repeatEvery),
                'weeks' => $date->addWeeks($repeatEvery),
                'months' => $date->addMonths($repeatEvery),
                'years' => $date->addYears($repeatEvery),
                default => $date,
            };
        }

        return $date;
    }
}