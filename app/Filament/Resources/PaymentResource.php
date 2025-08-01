<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Payment::class;
    protected static ?string $slug = 'payments';
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
            'restore',
            'restore_any',
            'force_delete',
            'force_delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('User')
                    ->options(function () {
                        return User::whereHas('roles', function ($query) {
                            $query->where('name', 'user');
                        })
                            ->whereHas('invoices', function ($query) {
                                $query->where('status', '!=', 'paid');
                            })
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->native(false)
                    ->columnSpanFull()
                    ->reactive()
                    ->prefixIcon('heroicon-o-user')
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('invoicePayments', []);
                    })
                    ->required(),

                DatePicker::make('date')
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->maxDate(now()),

                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0),

                Section::make('Invoices')
                    ->schema([
                        Repeater::make('invoicePayments')
                            ->label('Invoice Payments')
                            ->relationship('invoicePayments')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->options(function (Get $get) {
                                        $userId = $get('../../user_id');
                                        if (!$userId) return [];

                                        // Semua invoice eligible
                                        $invoices = Invoice::where('user_id', $userId)
                                            ->where('status', '!=', 'paid')
                                            ->pluck('code', 'id');

                                        // Semua invoice_id yang sudah dipilih di semua baris
                                        $selectedInvoiceIds = collect($get('../../invoicePayments'))
                                            ->pluck('invoice_id')
                                            ->filter()
                                            ->all();

                                        // Dapatkan invoice_id baris ini
                                        $currentInvoiceId = $get('invoice_id');

                                        // Filter: invoice yang belum dipilih ATAU invoice ini sendiri
                                        $availableInvoices = $invoices->reject(function ($code, $id) use ($selectedInvoiceIds, $currentInvoiceId) {
                                            return in_array($id, $selectedInvoiceIds) && $id != $currentInvoiceId;
                                        });

                                        return $availableInvoices->toArray();
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->reactive()
                                    ->required()
                                    ->columnSpanFull()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (!$state) {
                                            $set('invoice_number', null);
                                            $set('outstanding', null);
                                            return;
                                        }

                                        $invoice = Invoice::with(['invoiceItems', 'invoicePayments'])
                                            ->find($state);

                                        if ($invoice) {
                                            $outstanding = $invoice->invoiceItems->sum('rate') - $invoice->invoicePayments->sum('amount_applied');
                                            $set('invoice_number', $invoice->code);
                                            $set('outstanding', $outstanding);
                                        } else {
                                            $set('invoice_number', null);
                                            $set('outstanding', null);
                                        }
                                    }),

                                TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->disabled(),

                                TextInput::make('outstanding')
                                    ->label('Outstanding')
                                    ->prefix('Rp')
                                    ->disabled(),

                                TextInput::make('amount_applied')
                                    ->label('Amount Applied')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(fn ($context) => $context === 'create'),
                            ])
                            ->columns(3)
                            ->reactive()
                            ->visible(fn(Get $get) => !empty($get('user_id')))
                            ->required()
                            ->minItems(1)
                            ->deletable(fn($state, $get) => count($get('invoicePayments')) > 1),
                    ]),

                Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                    ])
                    ->native(false)
                    ->required(),

                Select::make('bank_account_id')
                    ->options(fn() => BankAccount::with('bank')->where('is_active', true)->get()->mapWithKeys(
                        fn(BankAccount $ba) => [$ba->id => $ba->bank?->short_name ?? '-']
                    )->toArray())
                    ->native(false)
                    ->requiredIf('payment_method', 'bank_transfer'),

                Textarea::make('note')
                    ->rows(3)
                    ->maxLength(500)
                    ->autosize()
                    ->helperText('Hanya untuk catatan internal, tidak akan ditampilkan pada laporan atau invoice.')
                    ->columnSpanFull(),

                Grid::make()
                    ->columns()
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created Date')
                            ->visible(fn(?Payment $record): bool => $record?->exists ?? false)
                            ->content(fn(?Payment $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Last Modified Date')
                            ->visible(fn(?Payment $record): bool => $record?->exists ?? false)
                            ->content(fn(?Payment $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                    ])
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_id'),

                TextColumn::make('serial_number'),

                TextColumn::make('reference_number'),

                TextColumn::make('date')
                    ->date(),

                TextColumn::make('amount'),

                TextColumn::make('payment_method'),

                TextColumn::make('bank_account_id'),

                TextColumn::make('note'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug'];
    }
}
