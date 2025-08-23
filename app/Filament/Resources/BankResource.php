<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankResource\Pages;
use App\Models\Bank;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Bank::class;
    protected static ?string $slug = 'banks';
    protected static ?string $navigationLabel = 'Bank';

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
        return $form
            ->schema([
                TextInput::make('short_name')
                    ->label('Nama Singkatan')
                    ->required()
                    ->placeholder('Masukkan Nama Singkatan'),

                TextInput::make('full_name')
                    ->label('Nama Lengkap')
                    ->placeholder('Masukkan Nama Lengkap'),

                SpatieMediaLibraryFileUpload::make('logo')
                    ->collection('logo')
                    ->label('Bank Logo')
                    ->image()
                    ->disk('s3')
                    ->visibility('private')
                    ->maxSize(200)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('#')
                    ->collection('logo')
                    ->label('Logo')
                    ->disk('s3')
                    ->visibility('private'),

                TextColumn::make('short_name')
                    ->label('Nama Singkatan')
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanks::route('/'),
            /*'create' => Pages\CreateBank::route('/create'),
            'edit' => Pages\EditBank::route('/{record}/edit'),*/
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
