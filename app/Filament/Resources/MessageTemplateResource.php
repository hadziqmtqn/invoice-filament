<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateResource\Pages;
use App\Filament\Resources\MessageTemplateResource\Schemas\MessageTemplateForm;
use App\Filament\Resources\MessageTemplateResource\Schemas\MessageTemplateTable;
use App\Models\MessageTemplate;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class MessageTemplateResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = MessageTemplate::class;
    protected static ?string $slug = 'message-templates';
    protected static ?string $navigationLabel = 'Templat Pesan';

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return MessageTemplateForm::form($form);
    }

    public static function table(Table $table): Table
    {
        return MessageTemplateTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageTemplates::route('/'),
            'view' => Pages\ViewMessageTemplate::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
