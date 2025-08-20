<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Enums\DataStatus;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalPaymentOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Paid', 'Rp' . number_format($this->totalPayment(DataStatus::PAID->value),0,',','.'))
                ->label('')
                ->description('Total Paid')
                ->color('success'),

            Stat::make('Unpaid', 'Rp' . number_format($this->totalPayment(DataStatus::PENDING->value),0,',','.'))
                ->label('')
                ->description('Unpaid')
                ->color('warning'),

            Stat::make('Cancelled', 'Rp' . number_format($this->totalPayment(DataStatus::EXPIRE->value),0,',','.'))
                ->label('')
                ->description('Cancelled')
                ->color('danger'),
        ];
    }

    private function totalPayment($status = null)
    {
        return Payment::when($status === DataStatus::PAID->value, fn($query) => $query->filterByStatus(DataStatus::PAID->value))
            ->when($status === DataStatus::PENDING->value, fn($query) => $query->filterByStatus(DataStatus::PENDING->value))
            ->when($status === DataStatus::EXPIRE->value, function ($query) {
                $query->whereNotIn('status', [DataStatus::PENDING->value, DataStatus::PAID->value]);
            })
            ->sum('amount');
    }
}
