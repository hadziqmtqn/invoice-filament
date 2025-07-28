<?php

namespace App\Filament\Resources\Main\UserResource\Pages;

use App\Filament\Resources\Main\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $profileData = $this->data['userProfile'] ?? [];

        // Pastikan semua key ada (agar field bisa diupdate ke null jika kosong)
        $fields = ['phone', 'province', 'city', 'district', 'village', 'street'];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $profileData)) {
                $profileData[$field] = null;
            }
        }
        $this->record->userProfile()->updateOrCreate([], $profileData);

        $this->record->refresh();
        $this->fillForm();
    }
}
