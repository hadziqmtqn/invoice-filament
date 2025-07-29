<?php

namespace App\Filament\Resources\Main\ApplicationResource\Pages;

use App\Filament\Resources\Main\ApplicationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
