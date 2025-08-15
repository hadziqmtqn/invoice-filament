<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel, HasColor
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';

    public function getLabel(): ?string
    {
        // TODO: Implement getLabel() method.
        return match ($this) {
            self::CASH => __('Cash'),
            self::BANK_TRANSFER => __('Bank Transfer')
        };
    }

    public function getColor(): string|array|null
    {
        // TODO: Implement getColor() method.
        return match ($this) {
            self::CASH => 'warning',
            self::BANK_TRANSFER => 'primary'
        };
    }

    public static function options(): array
    {
        return [
            self::CASH->value => self::CASH->getLabel(),
            self::BANK_TRANSFER->value => self::BANK_TRANSFER->getLabel()
        ];
    }
}
