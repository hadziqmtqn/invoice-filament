<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use App\Jobs\ChangeAuthenticationMessageJob;

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
        $user = $this->record;
        $original = $user->getOriginal();

        $emailChanged = $user->email !== $original['email'];
        $plainPassword = request('data.password'); // pastikan nama key sesuai struktur form
        $passwordChanged = !empty($plainPassword);

        if ($emailChanged || $passwordChanged) {
            ChangeAuthenticationMessageJob::dispatch([
                'user_name' => $user->name,
                'email' => $user->email,
                'password' => $passwordChanged ? $plainPassword : null,
            ], $user->userProfile?->phone);
        }
    }
}