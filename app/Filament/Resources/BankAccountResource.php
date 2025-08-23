<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\Bank;
use App\Models\BankAccount;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BankAccountResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = BankAccount::class;
    protected static ?string $slug = 'bank-accounts';
    protected static ?string $navigationLabel = 'Rekening Bank';

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
                Select::make('bank_id')
                    ->label('Bank')
                    ->options(Bank::pluck('short_name', 'id')->toArray())
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('account_name')
                    ->label('Nama Pemilik Rekening')
                    ->required()
                    ->placeholder('Masukkan Nama Pemilik Rekening'),

                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->required()
                    ->placeholder('Masukkan Nomor Rekening'),

                // hanya ada saat edit data
                ToggleButtons::make('is_active')
                    ->label('Status')
                    ->default(true)
                    ->boolean()
                    ->inline(),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SelectColumn::make('bank_id')
                    ->label('Bank')
                    ->options(Bank::pluck('short_name', 'id')->toArray())
                    ->searchable()
                    ->selectablePlaceholder(false)
                    ->sortable(),

                TextColumn::make('account_name')
                    ->label('Nama Pemilik Rekening')
                    ->searchable(),

                TextColumn::make('account_number')
                    ->label('Nomor Rekening')
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            /*'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),*/
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
