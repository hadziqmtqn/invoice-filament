<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;

enum RecurrenceFrequency: string implements HasColor
{
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
            self::DAYS => 'primary',
            self::WEEKS => 'secondary',
            self::MONTHS => 'warning',
            self::YEARS => 'info',
        };
    }

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::DAYS => __('Days'),
            self::WEEKS => __('Weeks'),
            self::MONTHS => __('Months'),
            self::YEARS => __('Years'),
        };
    }

    /**
     * @return string[]
     */
    public static function options(): array
    {
        return [
            self::DAYS->value => self::DAYS->label(),
            self::WEEKS->value => self::WEEKS->label(),
            self::MONTHS->value => self::MONTHS->label(),
            self::YEARS->value => self::YEARS->label(),
        ];
    }
}
