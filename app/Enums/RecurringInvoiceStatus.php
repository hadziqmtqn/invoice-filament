<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RecurringInvoiceStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case DISCONTINUED = 'discontinued';

    public function getColor(): string|array|null
    {
        // TODO: Implement getColor() method.
        return match ($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'success',
            self::DISCONTINUED => 'danger',
        };
    }

    public function getLabel(): ?string
    {
        // TODO: Implement getLabel() method.
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::ACTIVE => __('Active'),
            self::DISCONTINUED => __('Discontinued'),
        };
    }

    public function getLabelAlternative(): ?string
    {
        // TODO: Implement getLabel() method.
        return match ($this) {
            self::DRAFT => __('Konsep'),
            self::ACTIVE => __('Aktif'),
            self::DISCONTINUED => __('Dihentikan'),
        };
    }

    public function getIcon(): ?string
    {
        // TODO: Implement getIcon() method.
        return match ($this) {
            self::DRAFT => 'heroicon-o-document-text',
            self::ACTIVE => 'heroicon-o-check-circle',
            self::DISCONTINUED => 'heroicon-o-x-circle',
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

    public static function colors(): array
    {
        return [
            self::DRAFT->value => self::DRAFT->getColor(),
            self::ACTIVE->value => self::ACTIVE->getColor(),
            self::DISCONTINUED->value => self::DISCONTINUED->getColor(),
        ];
    }
}
