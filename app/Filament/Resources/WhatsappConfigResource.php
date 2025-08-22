<?php

namespace App\Filament\Resources;

use App\Enums\WhatsappGatewayProvider;
use App\Filament\Resources\WhatsappConfigResource\Pages;
use App\Models\WhatsappConfig;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class WhatsappConfigResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = WhatsappConfig::class;
    protected static ?string $slug = 'whatsapp-configs';
    protected static ?string $navigationLabel = 'Whatsapp Gateway';

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
                Select::make('provider')
                    ->options(WhatsappGatewayProvider::options())
                    ->searchable()
                    ->required()
                    ->reactive(),

                TextInput::make('api_domain')
                    ->label('API Domain')
                    ->required()
                    ->placeholder('Masukkan API Domain'),

                TextInput::make('secret_key')
                    ->required(fn(Get $get): bool => $get('provider') === WhatsappGatewayProvider::WABLAS->value)
                    ->placeholder('Masukkan Secret Key'),

                TextInput::make('api_key')
                    ->label('API Key')
                    ->required()
                    ->placeholder('Masukkan API Key'),

                ToggleButtons::make('is_active')
                    ->label('Status')
                    ->default(true)
                    ->inline()
                    ->boolean(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->searchable(),

                TextColumn::make('api_domain')
                    ->label('API Domain')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('secret_key'),

                TextColumn::make('api_key')
                    ->label('API Key')
                    ->searchable(),

                ToggleColumn::make('is_active')
                    ->label('Status')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappConfigs::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
