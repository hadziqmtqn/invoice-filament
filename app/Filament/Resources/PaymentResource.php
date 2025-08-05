<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Application;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Torgodly\Html2Media\Tables\Actions\Html2MediaAction;

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
                // TODO Main data
                Group::make()
                    ->schema([
                        Section::make()
                            ->columns()
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
                                            ->orderByDesc('created_at')
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
                                    ->maxDate(now())
                                    ->closeOnDateSelection(),

                                TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->reactive()
                                    ->rule(function (Get $get) {
                                        return function ($attribute, $value, $fail) use ($get) {
                                            $invoicePayments = $get('invoicePayments') ?? [];
                                            $totalApplied = collect($invoicePayments)->sum('amount_applied');
                                            if ($value < $totalApplied) {
                                                $fail('Total Amount tidak boleh kurang dari jumlah Amount Applied pada invoice.');
                                            }
                                        };
                                    }),
                            ]),

                        Section::make('Invoices')
                            ->schema([
                                Repeater::make('invoicePayments')
                                    ->label('Invoice Payments')
                                    ->relationship('invoicePayments')
                                    ->columnSpanFull()
                                    ->schema([
                                        Grid::make()
                                            ->columns()
                                            ->schema([
                                                Select::make('invoice_id')
                                                    ->label('Invoice')
                                                    ->options(function (Get $get) {
                                                        $userId = $get('../../user_id');
                                                        if (!$userId) return [];

                                                        // Semua invoice eligible
                                                        $invoices = Invoice::where('user_id', $userId)
                                                            ->where('status', '!=', 'paid')
                                                            ->orderByDesc('created_at')
                                                            ->pluck('title', 'id');

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
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if (!$state) {
                                                            $set('invoice_number', null);
                                                            $set('outstanding', null);
                                                            return;
                                                        }

                                                        $invoice = Invoice::with(['invoiceItems', 'invoicePayments'])
                                                            ->find($state);

                                                        if ($invoice) {
                                                            $totalBill = $invoice->invoiceItems->sum(fn($item) => $item->rate * $item->qty);
                                                            $outstanding = $totalBill - $invoice->invoicePayments->sum('amount_applied');
                                                            $set('invoice_number', $invoice->code);
                                                            $set('outstanding', $outstanding);
                                                        } else {
                                                            $set('invoice_number', null);
                                                            $set('outstanding', null);
                                                        }
                                                    }),

                                                TextInput::make('invoice_number')
                                                    ->label('Invoice Number')
                                                    ->disabled()
                                                    ->afterStateHydrated(function ($state, callable $set, $get) {
                                                        $invoiceId = $get('invoice_id');
                                                        if ($invoiceId) {
                                                            $invoice = Invoice::find($invoiceId);
                                                            $set('invoice_number', $invoice?->code ?? '');
                                                        }
                                                    }),
                                            ]),

                                        Grid::make()
                                            ->columns(3)
                                            ->schema([
                                                TextInput::make('outstanding')
                                                    ->label('Outstanding')
                                                    ->prefix('Rp')
                                                    ->disabled()
                                                    ->afterStateHydrated(function ($state, callable $set, Get $get) {
                                                        $invoiceId = $get('invoice_id');
                                                        if ($invoiceId) {
                                                            $invoice = Invoice::with(['invoiceItems', 'invoicePayments'])->find($invoiceId);

                                                            if ($invoice) {
                                                                $total = $invoice->invoiceItems->sum(fn($item) => $item->rate * $item->qty);
                                                                $allPaid = $invoice->invoicePayments->sum('amount_applied');

                                                                // Jika sedang edit Payment, ambil id Payment ini
                                                                $currentInvoicePaymentId = $get('id'); // Jika invoicePayments memiliki kolom id

                                                                // Cari amount_applied yang sedang diedit (jika ada id di repeater)
                                                                $currentApplied = 0;
                                                                if ($currentInvoicePaymentId) {
                                                                    $currentPayment = $invoice->invoicePayments->firstWhere('id', $currentInvoicePaymentId);
                                                                    if ($currentPayment) {
                                                                        $currentApplied = $currentPayment->amount_applied;
                                                                    }
                                                                }

                                                                // Outstanding = total - (total paid - currentApplied)
                                                                $outstanding = $total - ($allPaid - $currentApplied);

                                                                $set('outstanding', $outstanding);
                                                            }
                                                        }
                                                    }),

                                                TextInput::make('amount_applied')
                                                    ->label('Amount Applied')
                                                    ->prefix('Rp')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                                        $outstanding = intval($get('outstanding'));
                                                        $amountApplied = intval($get('amount_applied'));
                                                        $sisa = max($outstanding - $amountApplied, 0);
                                                        $set('rest_bill', $sisa);
                                                    })
                                                    ->required(fn ($context) => $context === 'create')
                                                    ->rule(function (Get $get) {
                                                        return function ($attribute, $value, $fail) use ($get) {
                                                            $outstanding = $get('outstanding') ?? 0;
                                                            if ($value > $outstanding) {
                                                                $fail('Amount Applied tidak boleh melebihi Outstanding.');
                                                            }
                                                        };
                                                    }),

                                                TextInput::make('rest_bill')
                                                    ->prefix('Rp')
                                                    ->disabled()
                                                    ->afterStateHydrated(function ($state, callable $set, $get) {
                                                        $outstanding = intval($get('outstanding'));
                                                        $amountApplied = intval($get('amount_applied'));
                                                        $sisa = max($outstanding - $amountApplied, 0);
                                                        $set('rest_bill', $sisa);
                                                    })
                                            ])
                                    ])
                                    ->reactive()
                                    ->visible(fn(Get $get) => !empty($get('user_id')))
                                    ->required()
                                    ->minItems(1)
                                    ->deletable(fn($state, $get) => count($get('invoicePayments')) > 1)
                                    ->addActionLabel('Add Item'),
                            ]),

                        Section::make('Payment Method')
                            ->description('Pilih metode pembayaran yang sesuai.')
                            ->columns()
                            ->schema([
                                Select::make('payment_method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                    ])
                                    ->native(false)
                                    ->required(),

                                Select::make('bank_account_id')
                                    ->label('Bank Account')
                                    ->options(function () {
                                        return BankAccount::with('bank')
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(fn(BankAccount $ba) => [$ba->id => $ba->bank?->short_name ?? '-'])
                                            ->toArray();
                                    })
                                    ->native(false)
                                    ->required(fn(Get $get) => $get('payment_method') === 'bank_transfer'),
                            ])
                    ])
                    ->columnSpan(['lg' => 2]),

                // TODO Summary & Attachments
                Group::make()
                    ->schema([
                        Section::make('Summary')
                            ->columns()
                            ->schema([
                                Placeholder::make('total_bill')
                                    ->label('Total Bill')
                                    ->content(function (Get $get) {
                                        $invoicePayments = $get('invoicePayments') ?? [];
                                        $totalOutstanding = 0;
                                        foreach ($invoicePayments as $row) {
                                            // Pastikan outstanding berupa angka
                                            $outstanding = intval($row['outstanding'] ?? 0);
                                            $totalOutstanding += $outstanding;
                                        }
                                        return new HtmlString('<div style="font-size:15pt; color:#0066cc"><b>Rp' . number_format($totalOutstanding, 0, ',', '.') . '</b></div>');
                                    })
                                    ->reactive()
                                    ->columnSpanFull(),

                                Placeholder::make('total_Pay')
                                    ->label('Total Pay')
                                    ->content(function (Get $get) {
                                        return new HtmlString('<div style="font-size:15pt; color:#00bb00"><b>Rp' . number_format(intval($get('amount') ?? 0), 0, ',', '.') . '</b></div>');
                                    })
                                    ->reactive()
                                    ->columnSpanFull(),

                                Placeholder::make('sum_rest_bill')
                                    ->label('Rest Bill')
                                    ->content(function (Get $get) {
                                        // Ambil total_amount: hitung dari sum outstanding invoicePayments
                                        $invoicePayments = $get('invoicePayments') ?? [];
                                        $totalOutstanding = 0;
                                        foreach ($invoicePayments as $row) {
                                            $outstanding = intval($row['outstanding'] ?? 0);
                                            $totalOutstanding += $outstanding;
                                        }

                                        // Ambil total_pay dari field 'amount'
                                        $totalPay = intval($get('amount') ?? 0);

                                        // Hitung sisa tagihan
                                        return new HtmlString(
                                            '<div style="font-size:15pt; color:#bb0000"><b>Rp' . number_format(max($totalOutstanding - $totalPay, 0), 0, ',', '.') . '</b></div>'
                                        );
                                    })
                                    ->reactive()
                                    ->columnSpanFull(),
                            ]),

                        Section::make()
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('attachment')
                                    ->collection('payment_attachments')
                                    ->label('Attachment')
                                    ->disk('s3')
                                    ->visibility('private')
                                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                                    ->maxSize(1024)
                                    ->openable()
                                    ->helperText('Optional, upload a receipt or proof of payment.'),

                                Textarea::make('note')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->autosize()
                                    ->helperText('Hanya untuk catatan internal, tidak akan ditampilkan pada laporan atau invoice.'),
                            ])
                    ])
                    ->columnSpan(['lg' => 1]),

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
            ])
            ->columns(3);
    }

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
                TrashedFilter::make(),
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
            ])
            ->actions([
                ActionGroup::make([
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
                        ->color('primary'),
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->modalContent()
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
        return ['reference_number'];
    }
}
