<?php

namespace App\Enums;

enum ItemUnit: string
{
    case Kg = 'kg';
    case Lb = 'lb';
    case Cm = 'cm';
    case m = 'm';
    case g = 'g';
    case Ltr = 'ltr';
    case pcs = 'pcs';
    case set = 'set';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Kg => 'Kilogram',
            self::Lb => 'Pound',
            self::Cm => 'Centimeter',
            self::m => 'Meter',
            self::g => 'Gram',
            self::Ltr => 'Liter',
            self::pcs => 'Pieces',
            self::set => 'Set',
            self::Other => 'Other',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
