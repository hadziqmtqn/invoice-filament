<?php

namespace App\Filament\Resources\ItemResource\Schemas;

use App\Enums\ProductType;
use Exception;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemTable
{
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
                    ->color(fn(string $state): string => ProductType::tryFrom($state)?->getColor() ?? 'gray')
                    ->formatStateUsing(fn(string $state): string => ProductType::tryFrom($state)->getLabel()),

                TextColumn::make('name')
                    ->description(fn($record): string => $record->item_name ?? '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit'),

                TextColumn::make('rate')
                    ->searchable()
                    ->money('idr'),

                ToggleColumn::make('is_active')
                    ->sortable()
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('product_type')
                    ->options(ProductType::options())
                    ->label('Product Type')
                    ->query(fn(Builder $query, array $data): Builder => $data['value'] ? $query->where('product_type', $data['value']) : $query)
                    ->native(false),

                TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
