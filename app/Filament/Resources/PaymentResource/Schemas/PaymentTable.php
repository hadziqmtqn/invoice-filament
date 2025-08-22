<?php

namespace App\Filament\Resources\PaymentResource\Schemas;

use App\Enums\DataStatus;
use App\Enums\PaymentSource;
use App\Models\Application;
use App\Models\Payment;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Torgodly\Html2Media\Tables\Actions\Html2MediaAction;

class PaymentTable
{
    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->description(fn($record) => $record->user?->userProfile?->phone ?? '-')
                    ->searchable(),

                TextColumn::make('reference_number')
                    ->label('No. Pembayaran')
                    ->searchable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('idr')
                    ->prefix('Rp')
                    ->numeric(0, ',', '.')
                    ->searchable()
                    ->summarize([
                        Sum::make('amount')
                            ->money('idr')
                            ->prefix('Rp')
                            ->numeric(0, ',', '.')
                    ]),

                TextColumn::make('payment_source')
                    ->label('Sumber Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => PaymentSource::tryFrom($state)?->getLabel() ?? 'N/A')
                    ->color(fn(string $state): string => PaymentSource::tryFrom($state)?->getColor() ?? 'gray')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => DataStatus::tryFrom($state)?->getLabel() ?? 'N/A')
                    ->color(fn(string $state): string => DataStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->sortable(),

                TextColumn::make('bankAccount.bank.short_name')
                    ->label('Bank Tujuan')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(DataStatus::options(['paid', 'pending', 'expire']))
                    ->native(false),

                SelectFilter::make('payment_source')
                    ->label('Sumber Pembayaran')
                    ->options(PaymentSource::dropdownOptions())
                    ->native(false),

                Filter::make('date')
                    ->label('Tanggal')
                    ->form([
                        DatePicker::make('start')
                            ->label('Dari Tanggal')
                            ->native(false)
                            ->placeholder('Dari Tanggal'),
                        DatePicker::make('end')
                            ->label('Sampai Tanggal')
                            ->native(false)
                            ->placeholder('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['start']) && !empty($data['end'])) {
                            $query->whereBetween('date', [$data['start'], $data['end']]);
                        }
                    }),

                QueryBuilder::make('invoicePayments')
                    ->constraints([
                        QueryBuilder\Constraints\TextConstraint::make('invoiceCode')
                            ->relationship('invoicePayments.invoice', 'code'),
                    ])
            ], layout: FiltersLayout::Modal)
            ->actions([
                Html2MediaAction::make('print')
                    ->icon('heroicon-o-printer')
                    ->modalHeading('Print Payment')
                    ->filename(fn(Payment $record) => 'Payment-' . $record->reference_number . '-' . now()->format('Y-m-d') . '.pdf')
                    ->modalContent(fn(Payment $record) => view('filament.resources.payment-resource.modal', [
                        'application' => Application::first(),
                        'payment' => $record->loadMissing('user.userProfile', 'invoicePayments.invoice.invoiceItems', 'bankAccount.bank:id,short_name'),
                    ]))
                    ->content(fn(Payment $record) => view('filament.resources.payment-resource.print', [
                        'application' => Application::first(),
                        'payment' => $record->loadMissing('user.userProfile', 'invoicePayments.invoice.invoiceItems', 'bankAccount.bank:id,short_name'),
                    ]))
                    ->savePdf()
                    ->color('warning'),
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->modalWidth('5xl'),
                    EditAction::make()
                        ->visible(fn(Payment $record): bool => $record->status === DataStatus::PENDING->value && $record->payment_source !== PaymentSource::PAYMENT_GATEWAY->value),
                ])
                    ->link()
                    ->label('Actions')
            ])
            ->bulkActions([
                //
            ]);
    }
}
