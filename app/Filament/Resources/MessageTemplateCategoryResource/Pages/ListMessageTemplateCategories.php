<?php

namespace App\Filament\Resources\MessageTemplateCategoryResource\Pages;

use App\Filament\Resources\MessageTemplateCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMessageTemplateCategories extends ListRecords
{
    protected static string $resource = MessageTemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Baru')
                ->slideOver()
                ->closeModalByClickingAway(false),
        ];
    }
}
