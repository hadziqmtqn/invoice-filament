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
            self::SECONDS => __('Seconds'),
            self::MINUTES => __('Minutes'),
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
            self::SECONDS->value => self::SECONDS->getLabel(),
            self::MINUTES->value => self::MINUTES->getLabel(),
            self::DAYS->value => self::DAYS->getLabel(),
            self::WEEKS->value => self::WEEKS->getLabel(),
            self::MONTHS->value => self::MONTHS->getLabel(),
            self::YEARS->value => self::YEARS->getLabel(),
        ];
    }
}
