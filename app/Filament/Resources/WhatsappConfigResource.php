<?php

namespace App\Filament\Resources;

use App\Enums\WhatsappGatewayProvider;
use App\Filament\Resources\WhatsappConfigResource\Pages;
use App\Models\WhatsappConfig;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
                    ->required(),

                TextInput::make('api_domain')
                    ->required(),

                TextInput::make('secret_key'),

                TextInput::make('api_key')
                    ->required(),

                Checkbox::make('is_active')
                    ->label('Aktifkan Konfigurasi')
                    ->default(true),

                Grid::make()
                    ->columns()
                    ->visible(fn(?WhatsappConfig $whatsappConfig): bool => $whatsappConfig?->exists ?? false)
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created Date')
                            ->content(fn(?WhatsappConfig $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Last Modified Date')
                            ->content(fn(?WhatsappConfig $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->searchable(),

                TextColumn::make('api_domain'),

                TextColumn::make('secret_key'),

                TextColumn::make('api_key'),

                ToggleColumn::make('is_active')
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
            //'create' => Pages\CreateWhatsappConfig::route('/create'),
            //'edit' => Pages\EditWhatsappConfig::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
