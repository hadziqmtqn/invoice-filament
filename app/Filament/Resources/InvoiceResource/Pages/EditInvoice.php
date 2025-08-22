<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\DataStatus;
use App\Filament\Resources\InvoiceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;
    protected ?bool $hasDatabaseTransactions = true;
    protected static ?string $title = 'Edit Faktur';

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    // URL tujuan redirect jika tidak boleh edit
    protected function getRedirectUrl(): string
    {
        if ($this->canEdit()) {
            return static::getResource()::getUrl('view', ['record' => $this->record]);
        }else {
            return static::getResource()::getUrl('index');
        }
    }

    // Tambahkan mount untuk validasi akses edit
    public function mount($record): void
    {
        parent::mount($record);

        // notifying the user that they cannot edit the invoice
        if (!$this->canEdit()) {
            Notification::make()
                ->title('Tidak bisa diedit')
                ->body('Faktur ini tidak dapat diedit karena telah dibayar.')
                ->warning()
                ->send();
        }
    }

    protected function canEdit(): bool
    {
        return $this->record->status === DataStatus::DRAFT->value;
    }

    protected function canDelete(): bool
    {
        return $this->record->status === DataStatus::DRAFT->value;
    }
}