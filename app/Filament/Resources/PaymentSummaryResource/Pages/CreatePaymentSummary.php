<?php

namespace App\Filament\Resources\PaymentSummaryResource\Pages;

use App\Filament\Resources\PaymentSummaryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentSummary extends CreateRecord
{
    protected static string $resource = PaymentSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
