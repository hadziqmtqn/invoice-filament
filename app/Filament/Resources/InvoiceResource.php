<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Item;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoiceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Invoice::class;
    protected static ?string $slug = 'invoices';
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

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
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),

                DatePicker::make('date')
                    ->required()
                    ->native(false)
                    ->default(now()),

                DatePicker::make('due_date')
                    ->required()
                    ->native(false),

                Repeater::make('invoiceItems')
                    ->relationship('invoiceItems')
                    ->schema([
                        Select::make('item_id')
                            ->label('Item')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    // kosongkan jika tidak ada item
                                    $set('name', null);
                                    $set('rate', null);
                                    $set('description', null);
                                    $set('unit', null);
                                    return;
                                }

                                $item = Item::find($state);
                                if ($item) {
                                    $set('name', $item->name);
                                    $set('rate', $item->rate);
                                    $set('description', $item->description);
                                    $set('unit', $item->unit);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('name')->required()->readOnly()->hidden()->reactive(),
                        TextInput::make('qty')->numeric()->default(1)->required(),
                        TextInput::make('unit')->reactive(),
                        TextInput::make('rate')->numeric()->required()->reactive(),
                        Textarea::make('description')->rows(2)->reactive(),
                    ])
                    ->columnSpanFull()
                    ->columns(),

                TextInput::make('discount')
                    ->required()
                    ->numeric()
                    ->default(0),

                Textarea::make('note')
                    ->rows(3)
                    ->columnSpanFull(),

                Grid::make()
                    ->columns()
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created Date')
                            ->visible(fn(?Invoice $record): bool => $record?->exists ?? false)
                            ->content(fn(?Invoice $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Last Modified Date')
                            ->visible(fn(?Invoice $record): bool => $record?->exists ?? false)
                            ->content(fn(?Invoice $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serial_number'),

                TextColumn::make('code'),

                TextColumn::make('user_id'),

                TextColumn::make('date')
                    ->date(),

                TextColumn::make('due_date')
                    ->date(),

                TextColumn::make('discount'),

                TextColumn::make('note'),

                TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                /*BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),*/
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code'];
    }
}
