<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Invoice', 'Rp' . number_format(Invoice::get()->sum(function (Invoice $invoice) {
                return $invoice->total_price;
            }),0,',','.')),

            Stat::make('Total Paid', 'Rp' . number_format(Invoice::get()->sum(function (Invoice $invoice) {
                return $invoice->total_paid;
            }),0,',','.')),

            Stat::make('Total Unpaid', 'Rp' . number_format(Invoice::get()->sum(function (Invoice $invoice) {
                return $invoice->total_due;
            }),0,',','.')),
        ];
    }
}
