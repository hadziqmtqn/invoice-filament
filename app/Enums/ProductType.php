<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasLabel, HasColor, HasIcon
{
    case SERVICE = 'service';
    case GOODS = 'goods';

    public function getLabel(): ?string
    {
        // TODO: Implement getLabel() method.
        return match ($this) {
            self::SERVICE => __('Jasa'),
            self::GOODS => __('Barang'),
        };
    }

    public function getColor(): string|array|null
    {
        // TODO: Implement getColor() method.
        return match ($this) {
            self::SERVICE => 'primary',
            self::GOODS => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        // TODO: Implement getIcon() method.
        return match ($this) {
            self::SERVICE => 'heroicon-o-server-stack',
            self::GOODS => 'heroicon-o-computer-desktop',
        };
    }

    public static function options(): array
    {
        return [
            self::SERVICE->value => self::SERVICE->getLabel(),
            self::GOODS->value => self::GOODS->getLabel(),
        ];
    }
}
