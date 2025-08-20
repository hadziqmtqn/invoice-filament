<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DataStatus: string implements HasColor, HasLabel, HasIcon
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PENDING = 'pending';
    case EXPIRE = 'expire';

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::PAID => 'primary',
            self::UNPAID, self::OVERDUE, self::EXPIRE => 'danger',
            self::PARTIALLY_PAID, self::PENDING => 'warning',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'DRAFT',
            self::SENT => 'SENT',
            self::PAID => 'PAID',
            self::OVERDUE => 'OVERDUE',
            self::UNPAID => 'UNPAID',
            self::PARTIALLY_PAID => 'PARTIALLY PAID',
            self::PENDING => 'PENDING',
            self::EXPIRE => 'EXPIRE',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document-text',
            self::SENT => 'heroicon-o-paper-airplane',
            self::PAID => 'heroicon-o-check-circle',
            self::OVERDUE, self::EXPIRE => 'heroicon-o-exclamation-circle',
            self::UNPAID => 'heroicon-o-x-circle',
            self::PARTIALLY_PAID, self::PENDING => 'heroicon-o-minus-circle',
        };
    }

    public static function colors(array $cases = []): array
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
            ->mapWithKeys(fn($case) => [$case->value => $case->getColor()])
            ->toArray();
    }

    public static function icon(): array
    {
        return [
            self::DRAFT->value => self::DRAFT->getIcon(),
            self::SENT->value => self::SENT->getIcon(),
            self::PAID->value => self::PAID->getIcon(),
            self::OVERDUE->value => self::OVERDUE->getIcon(),
            self::UNPAID->value => self::UNPAID->getIcon(),
            self::PARTIALLY_PAID->value => self::PARTIALLY_PAID->getIcon(),
            self::PENDING->value => self::PENDING->getIcon(),
            self::EXPIRE->value => self::EXPIRE->getIcon(),
        ];
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
