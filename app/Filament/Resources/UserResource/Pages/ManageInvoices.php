<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\UserResource;
use App\Models\Invoice;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManageInvoices extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;
    protected static string $relationship = 'Invoices';
    protected static ?string $title = 'Faktur';
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->tooltip(fn($record): string => $record->invoice_number)
                    ->searchable(),

                TextColumn::make('title')
                    ->limit(30)
                    ->tooltip(fn($record): string => $record->title)
                    ->searchable(),

                TextColumn::make('date')
                    ->date(fn() => 'd M Y')
                    ->description(fn(Invoice $record): string => $record->due_date ? 'Due: ' . $record->due_date->format('d M Y') : 'No Due Date'),

                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('total_due')
                    ->label('Due')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'primary',
                        'paid' => 'success',
                        'unpaid', 'overdue' => 'danger',
                        'partially_paid' => 'warning',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                        'overdue' => 'Overdue',
                        'partially_paid' => 'Partially Paid',
                    ])
                    ->selectablePlaceholder(false)
                    ->native(false),
            ])
            ->headerActions([
                /*Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make(),*/
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                //Tables\Actions\EditAction::make(),
                /*Tables\Actions\DissociateAction::make(),
                Tables\Actions\DeleteAction::make(),*/
            ])
            ->bulkActions([
                /*Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),*/
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return InvoiceResource::infolist($infolist);
    }
}
