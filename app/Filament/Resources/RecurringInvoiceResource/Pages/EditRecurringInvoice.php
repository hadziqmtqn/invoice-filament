<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Pages;

use App\Filament\Resources\RecurringInvoiceResource;
use Filament\Resources\Pages\EditRecord;

class EditRecurringInvoice extends EditRecord
{
    protected static string $resource = RecurringInvoiceResource::class;
    protected ?bool $hasDatabaseTransactions = true;
    protected static ?string $title = 'Edit';

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
