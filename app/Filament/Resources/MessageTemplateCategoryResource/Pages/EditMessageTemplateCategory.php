<?php

namespace App\Filament\Resources\MessageTemplateCategoryResource\Pages;

use App\Filament\Resources\MessageTemplateCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMessageTemplateCategory extends EditRecord
{
    protected static string $resource = MessageTemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
