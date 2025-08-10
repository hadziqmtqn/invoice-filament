<?php

namespace App\Filament\Resources\MessageTemplateCategoryResource\Pages;

use App\Filament\Resources\MessageTemplateCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMessageTemplateCategory extends CreateRecord
{
    protected static string $resource = MessageTemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
