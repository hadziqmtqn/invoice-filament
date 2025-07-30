<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\EditAction;
use App\Jobs\ChangeAuthenticationMessageJob;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            EditAction::make()
                ->after(function ($record, $data) {
                    // $record = user setelah update, $data = form input (plain)
                    $original = $record->getOriginal();

                    $emailChanged = $data['email'] !== $original['email'];
                    $passwordChanged = !empty($data['password']);

                    if ($emailChanged || $passwordChanged) {
                        ChangeAuthenticationMessageJob::dispatch([
                            'user_name' => $record->name,
                            'email' => $record->email,
                            'password' => $passwordChanged ? $data['password'] : null,
                        ], $record->userProfile?->phone);
                    }
                }),
        ];
    }
}