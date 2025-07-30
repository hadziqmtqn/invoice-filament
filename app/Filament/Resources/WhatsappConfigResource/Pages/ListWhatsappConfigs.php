<?php

namespace App\Filament\Resources\WhatsappConfigResource\Pages;

use App\Filament\Resources\WhatsappConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappConfigs extends ListRecords
{
    protected static string $resource = WhatsappConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
