<?php

namespace App\Filament\Resources\InvoiceResource\Schemas;

use App\Enums\DataStatus;
use App\Models\Invoice;
use CodeWithKyrian\FilamentDateRange\Tables\Filters\DateRangeFilter;
use Exception;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceTable
{
    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->description(fn(Invoice $record): string => $record->user?->userProfile?->phone)
                    ->searchable(),

                TextColumn::make('title')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('date')
                    ->date(fn() => 'd M Y')
                    ->description(fn(Invoice $record): string => $record->due_date ? 'Due: ' . $record->due_date->format('d M Y') : 'No Due Date'),

                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->tooltip(fn(Invoice $record): string => 'Total Due: Rp' . number_format($record->total_due, 0, ',', '.'))
                    ->money('idr')
                    ->prefix('Rp')
                    ->numeric(0, ',', '.')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => DataStatus::tryFrom($state)?->getLabel())
                    ->color(fn($state) => DataStatus::tryFrom($state)?->getColor())
                    ->icon(fn($state) => DataStatus::tryFrom($state)?->getIcon())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->date(fn() => 'd M Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                DateRangeFilter::make('date')
                    ->label('Date Range'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DataStatus::options())
                    ->selectablePlaceholder(false)
                    ->native(false),
            ], layout: FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::Medium)
            ->defaultSort('serial_number', 'desc')
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn(Invoice $record): bool => $record->status !== 'paid')
                        ->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()
                        ->visible(fn(Invoice $record): bool => $record->status !== 'paid')
                        ->disabled(fn(Invoice $record): bool => $record->status !== 'paid' || $record->status !== 'partially_paid')
                        ->icon('heroicon-o-trash'),
                ])
                    ->link()
                    ->label('Actions'),
            ])
            ->bulkActions([
                //
            ]);
    }
}
