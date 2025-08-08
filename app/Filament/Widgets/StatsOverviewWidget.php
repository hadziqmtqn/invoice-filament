<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $productType = $this->filters['productType'] ?? null;
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $invoices = Invoice::query();

        if ($productType) {
            $invoices->whereHas('invoiceItems.item', function ($query) use ($productType) {
                $query->where('product_type', $productType);
            });
        }
        if ($startDate) $invoices->whereDate('date', '>=', $startDate);
        if ($endDate) $invoices->whereDate('date', '<=', $endDate);

        $filteredInvoices = $invoices->get();

        return [
            Stat::make(
                'Total Invoice',
                'Rp' . number_format($filteredInvoices->sum('total_price'),0,',','.')
            ),

            Stat::make(
                'Total Paid',
                'Rp' . number_format($filteredInvoices->sum('total_paid'),0,',','.')
            ),

            Stat::make(
                'Total Unpaid',
                'Rp' . number_format($filteredInvoices->sum('total_due'),0,',','.')
            ),
        ];
    }
}
