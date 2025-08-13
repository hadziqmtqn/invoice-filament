<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Resources\RecurringInvoiceResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ManageInvoices extends ManageRelatedRecords
{
    protected static string $resource = RecurringInvoiceResource::class;
    protected static string $relationship = 'invoices';
    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    public static function getNavigationLabel(): string
    {
        return 'Invoices';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('recurring_invoice_id')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_price')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('total_paid')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->description(fn($record) => 'Due: ' . $record->due_date->format('d M Y'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => InvoiceStatus::tryFrom($state)?->getLabel())
                    ->color(fn($state) => InvoiceStatus::tryFrom($state)?->getColor())
                    ->icon(fn($state) => InvoiceStatus::tryFrom($state)?->getIcon())
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->url(fn () => CreateInvoice::getUrl()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->url(fn ($record) => ViewInvoice::getUrl(['record' => $record])),
                    Tables\Actions\EditAction::make()
                        ->url(fn ($record) => EditInvoice::getUrl(['record' => $record])),
                ])
            ])
            ->bulkActions([
                //
            ]);
    }
}
