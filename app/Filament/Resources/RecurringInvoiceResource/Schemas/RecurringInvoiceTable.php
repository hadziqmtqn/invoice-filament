<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Schemas;

use App\Enums\RecurrenceFrequency;
use App\Enums\RecurringInvoiceStatus;
use Exception;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecurringInvoiceTable
{
    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->tooltip(fn($record) => $record->invoice_number)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->searchable(),

                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('date')
                    ->description(fn($record) => 'Due: ' . ($record->due_date?->format('d M Y') ?? '-'))
                    ->date('d M Y'),

                TextColumn::make('next_invoice_date')
                    ->date('d M Y H:i:s'),

                TextColumn::make('recurrence_frequency')
                    ->badge()
                    ->color(fn ($state) => RecurrenceFrequency::tryFrom($state)?->getColor() ?? 'gray')
                    ->formatStateUsing(fn ($state, $record) => $record->repeat_every . ' ' . RecurrenceFrequency::tryFrom($state)?->getLabel() ?? $state)
                    ->label('Repeat Every')
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('total_price')
                    ->money('idr'),

                TextColumn::make('status')
                    ->badge()
                    ->icon(fn($state) => RecurringInvoiceStatus::tryFrom($state)?->getIcon() ?? 'heroicon-o-question-mark-circle')
                    ->color(fn($state) => RecurringInvoiceStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->formatStateUsing(fn($state) => RecurringInvoiceStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(RecurringInvoiceStatus::options())
                    ->label('Status')
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                ])
            ])
            ->bulkActions([
                //
            ]);
    }
}
