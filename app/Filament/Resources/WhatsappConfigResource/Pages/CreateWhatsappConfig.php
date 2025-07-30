<?php

namespace App\Filament\Resources\WhatsappConfigResource\Pages;

use App\Filament\Resources\WhatsappConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsappConfig extends CreateRecord
{
    protected static string $resource = WhatsappConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
