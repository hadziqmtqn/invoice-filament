<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentSummaryResource\Pages;
use App\Models\Payment;
use Exception;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class PaymentSummaryResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $slug = 'payment-summaries';
    protected static ?string $label = 'Payment Summary';
    protected static ?string $navigationLabel = 'Payment Summary';
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 4;

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month_year')
                    ->label('Month')
                    ->formatStateUsing(fn ($state) => Carbon::createFromFormat('Y-m', $state)->translatedFormat('F Y')),
                TextColumn::make('total')
                    ->label('Total Pay')
                    ->money('IDR', true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentSummaries::route('/'),
        ];
    }
}
