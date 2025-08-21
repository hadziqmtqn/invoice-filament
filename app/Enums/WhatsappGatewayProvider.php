<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WhatsappGatewayProvider: string implements HasLabel
{
    case WABLAS = 'wablas';
    case WANESIA = 'wanesia';
    case FONNTE = 'fonnte';

    public function getLabel(): ?string
    {
        // TODO: Implement getLabel() method.
        return match ($this) {
            self::WABLAS => __('Wablas'),
            self::WANESIA => __('Wanesia'),
            self::FONNTE => __('Fonnte'),
        };
    }

    public static function options(): array
    {
        return [
            self::WABLAS->value => self::WABLAS->getLabel(),
            self::WANESIA->value => self::WANESIA->getLabel(),
            self::FONNTE->value => self::FONNTE->getLabel(),
        ];
    }
}
