<?php

namespace App\Filament\Resources\PaymentResource\Schemas;

use App\Models\Application;
use Exception;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                    ->money('idr', true)
                    ->searchable(),

                TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'warning',
                        'bank_transfer' => 'primary',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('bankAccount.bank.short_name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make()
                    ->native(false),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                    ])
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
                    ->filename(fn($record) => 'Payment-' . $record->reference_number . '-' . now()->format('Y-m-d') . '.pdf')
                    ->modalContent(fn($record) => view('filament.resources.payment-resource.modal', [
                        'application' => Application::first(),
                        'payment' => $record->loadMissing('user.userProfile', 'invoicePayments.invoice.invoiceItems', 'bankAccount.bank:id,short_name'),
                    ]))
                    ->content(fn($record) => view('filament.resources.payment-resource.print', [
                        'application' => Application::first(),
                        'payment' => $record->loadMissing('user.userProfile', 'invoicePayments.invoice.invoiceItems', 'bankAccount.bank:id,short_name'),
                    ]))
                    ->savePdf()
                    ->color('warning'),
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->modalWidth('5xl'),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->link()
                    ->label('Actions')
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
