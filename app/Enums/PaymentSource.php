<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentSource: string implements HasLabel, HasColor
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case PAYMENT_GATEWAY = 'payment_gateway';

    public function getLabel(): ?string
    {
        // TODO: Implement getLabel() method.
        return match ($this) {
            self::CASH => __('Cash'),
            self::BANK_TRANSFER => __('Bank Transfer'),
            self::PAYMENT_GATEWAY => __('Payment Gateway'),
        };
    }

    public function getColor(): string|array|null
    {
        // TODO: Implement getColor() method.
        return match ($this) {
            self::CASH => 'warning',
            self::BANK_TRANSFER => 'primary',
            self::PAYMENT_GATEWAY => 'info',
        };
    }

    public static function options(): array
    {
        return [
            self::CASH->value => self::CASH->getLabel(),
            self::BANK_TRANSFER->value => self::BANK_TRANSFER->getLabel()
        ];
    }

    public static function colors(): array
    {
        return [
            self::CASH->value => self::CASH->getColor(),
            self::BANK_TRANSFER->value => self::BANK_TRANSFER->getColor()
        ];
    }

    public static function dropdownOptions(): array
    {
        return [
            self::CASH->value => self::CASH->getLabel(),
            self::BANK_TRANSFER->value => self::BANK_TRANSFER->getLabel(),
            self::PAYMENT_GATEWAY->value => self::PAYMENT_GATEWAY->getLabel(),
        ];
    }
}
