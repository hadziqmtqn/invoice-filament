<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Pages;

use App\Filament\Resources\RecurringInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurringInvoice extends CreateRecord
{
    protected static string $resource = RecurringInvoiceResource::class;
    protected ?bool $hasDatabaseTransactions = true;
    protected static ?string $title = 'Buat Faktur Perulangan';

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
