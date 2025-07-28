<?php

namespace App\Filament\Resources\Main\UserResource\Pages;

use App\Filament\Resources\Main\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
