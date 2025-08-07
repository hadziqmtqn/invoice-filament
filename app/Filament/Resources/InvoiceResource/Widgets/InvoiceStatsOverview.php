<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListInvoices::class;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Invoices', $this->getPageTableQuery()->count())
                ->label('Total Invoices')
                ->color('primary'),

            Stat::make('Total Paid', number_format($this->getPageTableQuery()->get()->sum(fn($invoice) => $invoice->total_paid), 0,',','.'))
                ->label('Total Paid')
                ->color('success'),

            Stat::make('Total Unpaid', number_format($this->getPageTableQuery()->get()->sum(fn($invoice) => $invoice->total_due),0,',','.'))
                ->label('Total Unpaid')
                ->color('danger'),
        ];
    }
}
