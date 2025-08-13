<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel, HasIcon
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::PAID => 'primary',
            self::UNPAID, self::OVERDUE => 'danger',
            self::PARTIALLY_PAID => 'warning',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::UNPAID => 'Unpaid',
            self::PARTIALLY_PAID => 'Partially Paid',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document-text',
            self::SENT => 'heroicon-o-paper-airplane',
            self::PAID => 'heroicon-o-check-circle',
            self::OVERDUE => 'heroicon-o-exclamation-circle',
            self::UNPAID => 'heroicon-o-x-circle',
            self::PARTIALLY_PAID => 'heroicon-o-minus-circle',
        };
    }

    public static function color(): array
    {
        return [
            self::DRAFT->value => self::DRAFT->getColor(),
            self::SENT->value => self::SENT->getColor(),
            self::PAID->value => self::PAID->getColor(),
            self::OVERDUE->value => self::OVERDUE->getColor(),
            self::UNPAID->value => self::UNPAID->getColor(),
            self::PARTIALLY_PAID->value => self::PARTIALLY_PAID->getColor(),
        ];
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
        ];
    }

    public static function options(): array
    {
        return [
            self::DRAFT->value => self::DRAFT->getLabel(),
            self::SENT->value => self::SENT->getLabel(),
            self::PAID->value => self::PAID->getLabel(),
            self::OVERDUE->value => self::OVERDUE->getLabel(),
            self::UNPAID->value => self::UNPAID->getLabel(),
            self::PARTIALLY_PAID->value => self::PARTIALLY_PAID->getLabel(),
        ];
    }
}
