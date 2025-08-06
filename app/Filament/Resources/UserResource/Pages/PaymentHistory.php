<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentHistory extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;
    protected static string $relationship = 'Payments';
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationLabel(): string
    {
        return 'Payments History';
    }

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
            ->recordTitleAttribute('user_id')
            ->columns([
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
            ], layout: Tables\Enums\FiltersLayout::Modal)
            ->headerActions([
                /*Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make(),*/
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DissociateAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
                    ->link()
                    ->label('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
