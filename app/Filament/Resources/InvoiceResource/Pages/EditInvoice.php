<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;
    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->visible(fn() => $this->canDelete()),
        ];
    }

    // URL tujuan redirect jika tidak boleh edit
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    // Tambahkan mount untuk validasi akses edit
    public function mount($record): void
    {
        parent::mount($record);

        if (! $this->canEdit()) {
            $this->redirect($this->getRedirectUrl());
        }

        // notifying the user that they cannot edit the invoice
        if (! $this->canEdit()) {
            Notification::make()
                ->title('Cannot Edit Invoice')
                ->body('This invoice cannot be edited because it has already been paid.')
                ->warning()
                ->send();
        }
    }

    protected function canEdit(): bool
    {
        return !$this->record->status != 'paid' || !$this->record->status != 'partially_paid';
    }

    protected function canDelete(): bool
    {
        return $this->record->status === 'draft';
    }
}