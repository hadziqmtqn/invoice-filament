<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateCategoryResource\Pages;
use App\Models\MessageTemplateCategory;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessageTemplateCategoryResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = MessageTemplateCategory::class;
    protected static ?string $slug = 'message-template-categories';
    protected static ?string $navigationLabel = 'Kategori Pesan';

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->placeholder('Masukkan Nama Kategori')
                    ->columnSpanFull()
                    ->required(),

                MarkdownEditor::make('placeholder')
                    ->label('Placeholder')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'numberList',
                        'blockquote',
                        'horizontalRule',
                        'redo',
                        'undo',
                    ])
                    ->placeholder('Masukkan Placeholder')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                TextColumn::make('placeholder')
                    ->markdown()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageTemplateCategories::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
