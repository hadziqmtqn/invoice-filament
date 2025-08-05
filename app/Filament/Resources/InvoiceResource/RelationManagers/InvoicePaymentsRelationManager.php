<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicePaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'InvoicePayments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_id')
            ->columns([
                Tables\Columns\TextColumn::make('payment.reference_number')
                    ->label('Reference Number')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment.amount')
                    ->label('Amount')
                    ->money('idr')
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment.date')
                    ->label('Payment Date')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment.payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'cash' => 'success',
                        'bank_transfer' => 'primary',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn($state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                /*Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),*/
            ])
            ->bulkActions([
                /*Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),*/
            ]);
    }
}
