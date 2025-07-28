<?php

namespace App\Filament\Resources\Main\UserResource\Pages;

use App\Filament\Resources\Main\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pisahkan data userProfile dari $data utama
        $this->userProfileData = $data['userProfile'] ?? [];
        unset($data['userProfile']); // Hapus agar tidak error mass assignment
        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->userProfileData)) {
            $this->record->userProfile()->create($this->userProfileData);
        }

        // Tambahkan baris berikut agar form langsung update dengan data userProfile
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    // Properti untuk menyimpan sementara data userProfile
    protected array $userProfileData = [];
}
