<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Models\Item;
use Exception;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $slug = 'items';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('product_type')
                    ->required()
                    ->options([
                        'goods' => 'Barang',
                        'service' => 'Jasa',
                    ])
                    ->inline()
                    ->columnSpanFull(),

                TextInput::make('name')
                    ->required(),

                Select::make('unit')
                    ->options([
                        'pcs' => 'Pieces',
                        'kg' => 'Kilograms',
                        'ltr' => 'Liters',
                        'mtr' => 'Meters',
                        'box' => 'Box',
                        'set' => 'Set',
                    ]),

                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->minValue(10000),

                Textarea::make('description')
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->helperText('Optional, can be used to provide additional information about the item.'),

                // hanya muncul di halaman edit
                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->visible(fn(?Item $record): bool => $record?->exists ?? false)
                    ->content(fn(?Item $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->visible(fn(?Item $record): bool => $record?->exists ?? false)
                    ->content(fn(?Item $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'goods' => 'success',
                        'service' => 'warning',
                        default => 'secondary',
                    }),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit'),

                TextColumn::make('rate')
                    ->searchable()
                    ->money('idr', true)
                    ->formatStateUsing(fn($state): string => number_format($state, 0, ',', '.')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('product_type')
                    ->options([
                        'goods' => 'Barang',
                        'service' => 'Jasa',
                    ])
                    ->label('Product Type')
                    ->query(fn(Builder $query, array $data): Builder => $data['value'] ? $query->where('product_type', $data['value']) : $query),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
