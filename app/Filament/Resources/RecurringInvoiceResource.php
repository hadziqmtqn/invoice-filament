<?php

namespace App\Filament\Resources;

use App\Enums\RecurrenceFrequency;
use App\Filament\Resources\RecurringInvoiceResource\Pages;
use App\Models\RecurringInvoice;
use App\Services\UserService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecurringInvoiceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = RecurringInvoice::class;
    protected static ?string $slug = 'recurring-invoices';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Group::make([
                    Section::make()
                        ->columns()
                        ->schema([
                            Select::make('user_id')
                                ->label('User')
                                ->options(fn(?RecurringInvoice $invoice) => UserService::dropdownOptions($invoice?->exists ? $invoice->user_id : null))
                                ->required()
                                ->native(false)
                                ->prefixIcon('heroicon-o-user')
                                ->searchable()
                                ->columnSpanFull(),

                            DatePicker::make('date')
                                ->required()
                                ->default(now())
                                ->label('Invoice Date')
                                ->prefixIcon('heroicon-o-calendar')
                                ->native(false)
                                ->closeOnDateSelection(),

                            DatePicker::make('due_date')
                                ->required()
                                ->minDate(fn(Get $get) => $get('date'))
                                ->label('Due Date')
                                ->prefixIcon('heroicon-o-calendar')
                                ->native(false)
                                ->closeOnDateSelection(),
                        ]),
                ])
                    ->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make()
                        ->schema([
                            Select::make('recurrence_frequency')
                                ->options(RecurrenceFrequency::options())
                                ->required()
                                ->native(false),

                            TextInput::make('repeat_every')
                                ->required()
                                ->integer(),

                            TextInput::make('discount')
                                ->numeric()
                                ->suffix('%'),
                        ]),
                ])
                    ->columnSpan(['lg' => 1]),



                TextInput::make('note'),

                TextInput::make('status')
                    ->required(),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn(?RecurringInvoice $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn(?RecurringInvoice $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_number')
                    ->date(),

                TextColumn::make('serial_number'),

                TextColumn::make('code'),

                TextColumn::make('user_id'),

                TextColumn::make('date')
                    ->date(),

                TextColumn::make('due_date')
                    ->date(),

                TextColumn::make('recurrence_frequency'),

                TextColumn::make('repeat_every'),

                TextColumn::make('discount'),

                TextColumn::make('note'),

                TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringInvoices::route('/'),
            'create' => Pages\CreateRecurringInvoice::route('/create'),
            'edit' => Pages\EditRecurringInvoice::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug'];
    }
}
