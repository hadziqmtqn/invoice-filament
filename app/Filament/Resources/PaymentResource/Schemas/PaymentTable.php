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
                    ->description(fn($record) => $record->user?->userProfile?->phone ?? '-')
                    ->searchable(),

                TextColumn::make('reference_number')
                    ->searchable(),

                TextColumn::make('date')
                    ->date(fn() => 'd M Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('idr')
                    ->numeric(0, ',', '.')
                    ->searchable(),

                TextColumn::make('payment_source')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => PaymentSource::tryFrom($state)?->getLabel() ?? 'N/A')
                    ->color(fn(string $state): string => PaymentSource::tryFrom($state)?->getColor() ?? 'gray')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->sortable(),

                TextColumn::make('bankAccount.bank.short_name')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('payment_source')
                    ->options(PaymentSource::dropdownOptions())
                    ->native(false),

                Filter::make('date')
                    ->form([
                        DatePicker::make('start')
                            ->label('Start Date')
                            ->native(false)
                            ->placeholder('Start Date'),
                        DatePicker::make('end')
                            ->label('End Date')
                            ->native(false)
                            ->placeholder('End Date'),
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
