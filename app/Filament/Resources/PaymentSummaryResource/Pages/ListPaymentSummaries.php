<?php

namespace App\Filament\Resources\PaymentSummaryResource\Pages;

use App\Filament\Resources\PaymentSummaryResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPaymentSummaries extends ListRecords
{
    protected static string $resource = PaymentSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return $this->getModel()::query()
            ->selectRaw("MIN(id) as id, TO_CHAR(date, 'YYYY-MM') as month_year, SUM(amount) as total")
            ->where('status', 'paid')
            ->groupByRaw("TO_CHAR(date, 'YYYY-MM')")
            ->orderByRaw("MIN(date) DESC");
    }
}
