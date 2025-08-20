<?php

namespace App\Filament\Resources\PaymentSummaryResource\Pages;

use App\Filament\Resources\PaymentSummaryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentSummary extends EditRecord
{
    protected static string $resource = PaymentSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
