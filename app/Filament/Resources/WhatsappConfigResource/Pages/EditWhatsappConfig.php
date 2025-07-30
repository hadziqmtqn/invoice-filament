<?php

namespace App\Filament\Resources\WhatsappConfigResource\Pages;

use App\Filament\Resources\WhatsappConfigResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappConfig extends EditRecord
{
    protected static string $resource = WhatsappConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
