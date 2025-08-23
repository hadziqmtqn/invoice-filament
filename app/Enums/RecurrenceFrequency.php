<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RecurrenceFrequency: string implements HasColor, HasLabel
{
    case SECONDS = 'seconds';
    case MINUTES = 'minutes';
    case DAYS = 'days';
    case WEEKS = 'weeks';
    case MONTHS = 'months';
    case YEARS = 'years';

    /**
     * @return string|array|null
     */
    public function getColor(): string|array|null
    {
        // TODO: Implement getColor() method.
        return match ($this) {
            self::SECONDS, self::MINUTES => 'danger',
            self::DAYS => 'primary',
            self::WEEKS => 'secondary',
            self::MONTHS => 'warning',
            self::YEARS => 'info',
        };
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SECONDS => __('Detik'),
            self::MINUTES => __('Menit'),
            self::DAYS => __('Hari'),
            self::WEEKS => __('Minggu'),
            self::MONTHS => __('Bulan'),
            self::YEARS => __('Tahun'),
        };
    }

    public static function options(array $cases = []): array
    {
        $allCases = self::cases();

        // Jika $cases kosong, tampilkan semua
        if (empty($cases)) {
            $casesToShow = $allCases;
        } else {
            $casesToShow = array_filter($allCases, function($case) use ($cases) {
                // Cek apakah enum atau value ada di $cases
                return in_array($case, $cases, true) || in_array($case->value, $cases, true);
            });
        }

        return collect($casesToShow)
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
