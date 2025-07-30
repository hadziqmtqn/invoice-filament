<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\Bank;
use App\Models\BankAccount;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = BankAccount::class;
    protected static ?string $slug = 'bank-accounts';
    protected static ?string $navigationGroup = 'Konfigurasi';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 2;

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
                    ->required(),

                TextInput::make('account_number')
                    ->required(),

                // hanya ada saat edit data
                Checkbox::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Apakah rekening ini aktif?'),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->visible(fn(?BankAccount $record): bool => $record?->created_at !== null)
                    ->content(fn(?BankAccount $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->visible(fn(?BankAccount $record): bool => $record?->updated_at !== null)
                    ->content(fn(?BankAccount $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bank.short_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account_name')
                    ->searchable(),

                TextColumn::make('account_number')
                    ->searchable(),

                CheckboxColumn::make('is_active')
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
        return ['bank.short_name', 'account_name', 'account_number'];
    }
}
