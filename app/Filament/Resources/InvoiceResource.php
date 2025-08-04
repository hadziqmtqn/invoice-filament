<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Item;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class InvoiceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Invoice::class;
    protected static ?string $slug = 'invoices';
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

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
            ->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->columns()
                            ->schema([
                                Select::make('user_id')
                                    ->label('User')
                                    ->options(function (?Invoice $record) {
                                        return User::whereHas('roles', fn($query) => $query->where('name', 'user'))
                                            ->when($record?->exists, function ($query) use ($record) {
                                                $query->where('id', $record->user_id);
                                            })
                                            ->orderBy('name')
                                            ->limit(10)
                                            ->get()
                                            ->mapWithKeys(fn(User $user) => [$user->id => $user->name]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),

                                TextInput::make('title')
                                    ->label('Invoice Title')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpanFull(),

                                DatePicker::make('date')
                                    ->required()
                                    ->format('d M Y')
                                    ->native(false)
                                    ->default(now())
                                    ->closeOnDateSelection(),

                                DatePicker::make('due_date')
                                    ->required()
                                    ->format('d M Y')
                                    ->native(false)
                                    ->minDate(fn(Get $get) => $get('date'))
                                    ->closeOnDateSelection(),
                            ]),

                        Section::make('Invoice Items')
                            ->description('Tambahkan item yang akan ditagihkan dalam invoice ini.')
                            ->schema([
                                Repeater::make('invoiceItems')
                                    ->relationship('invoiceItems')
                                    ->schema([
                                        Select::make('item_id')
                                            ->label('Item')
                                            ->searchable()
                                            ->options(fn() => Item::all()->pluck('name', 'id'))
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->native(false)
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    // kosongkan jika tidak ada item
                                                    $set('name', null);
                                                    $set('rate', null);
                                                    $set('description', null);
                                                    $set('unit', null);
                                                    return;
                                                }

                                                $item = Item::find($state);
                                                if ($item) {
                                                    $set('name', $item->name);
                                                    $set('rate', $item->rate);
                                                    $set('description', $item->description);
                                                    $set('unit', $item->unit);
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Grid::make()
                                            ->columns(4)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->readOnly()
                                                    ->reactive(),

                                                TextInput::make('qty')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required()
                                                    ->reactive(),

                                                TextInput::make('unit')
                                                    ->reactive(),

                                                TextInput::make('rate')
                                                    ->numeric()
                                                    ->required()
                                                    ->reactive(),
                                            ]),
                                        Textarea::make('description')->rows(2)->reactive()->columnSpanFull(),
                                    ])
                                    ->minItems(1)
                                    ->deletable(fn($state, callable $get): bool => count($get('invoiceItems')) > 1)
                                    ->columnSpanFull()
                                    ->addActionLabel('Add Item')
                                    ->columns(),
                            ]),

                        Section::make()
                            ->schema([
                                Textarea::make('note')
                                    ->rows(3),
                            ]),
                    ])
                        ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Total')
                            ->description('Total harga akan dihitung berdasarkan item yang ditambahkan.')
                            ->schema([
                                TextInput::make('discount')
                                    ->label('Discount (%)')
                                    ->helperText('Masukkan diskon dalam persen, misal: 10 untuk 10%')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    ->suffix('%'),

                                Grid::make()
                                    ->schema([
                                        Placeholder::make('total_price')
                                            ->label('Total Harga')
                                            ->content(function (Get $get) {
                                                $items = $get('invoiceItems') ?? [];
                                                $total = 0;
                                                foreach ($items as $item) {
                                                    $qty = isset($item['qty']) ? (int) $item['qty'] : 0;
                                                    $rate = isset($item['rate']) ? (int) $item['rate'] : 0;
                                                    $total += $qty * $rate;
                                                }
                                                return (new HtmlString('<div style="font-size: 15pt"><strong>Rp' . number_format($total, 0, ',', '.') . '</strong></div>'));
                                            })
                                            ->columnSpanFull(),

                                        Placeholder::make('final_price')
                                            ->label('Total Akhir Setelah Diskon')
                                            ->content(function (Get $get) {
                                                $items = $get('invoiceItems') ?? [];
                                                $total = 0;
                                                foreach ($items as $item) {
                                                    $qty = isset($item['qty']) ? (int) $item['qty'] : 0;
                                                    $rate = isset($item['rate']) ? (int) $item['rate'] : 0;
                                                    $total += $qty * $rate;
                                                }
                                                $discount = (float) ($get('discount') ?? 0);
                                                $final = $total - ($discount / 100 * $total);

                                                return (new HtmlString('<div style="font-size: 17pt; color: #00bb00"><b>Rp' . number_format($final, 0, ',', '.') . '</b></div>'));
                                            })
                                            ->columnSpanFull(),
                                    ])
                            ]),

                        Section::make('Rekening Bank')
                            ->description('Transfer pembayaran ke salah satu rekening berikut:')
                            ->schema([
                                Placeholder::make('bank_accounts')
                                    ->content(function () {
                                        $accounts = BankAccount::with('bank:id,short_name,full_name')
                                            ->orderBy('bank_id')
                                            ->get();

                                        if ($accounts->isEmpty()) {
                                            return 'Belum ada data rekening bank.';
                                        }

                                        $html = '<div><ul>';
                                        foreach ($accounts as $account) {
                                            $html .= '<li><b>' . e($account->bank?->short_name) . '</b> - '
                                                . e($account->account_number) . ' a.n. '
                                                . e($account->account_name) . '</li>';
                                        }
                                        $html .= '</ul></div>';
                                        return new HtmlString($html);
                                    })
                            ]),

                        Section::make('Pembaruan')
                            ->visible(fn(?Invoice $record): bool => $record?->exists ?? false)
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        Placeholder::make('created_at')
                                            ->label('Created Date')
                                            ->content(fn(?Invoice $record): string => $record?->created_at?->diffForHumans() ?? '-')
                                            ->columnSpanFull(),

                                        Placeholder::make('updated_at')
                                            ->label('Last Modified Date')
                                            ->content(fn(?Invoice $record): string => $record?->updated_at?->diffForHumans() ?? '-')
                                            ->columnSpanFull(),
                                    ])
                            ])
                    ])
                        ->columnSpan(['lg' => 1]),
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
                TextColumn::make('code')
                    ->tooltip(fn($record): string => $record->invoice_number)
                    ->searchable(),

                TextColumn::make('title')
                    ->limit(30)
                    ->tooltip(fn($record): string => $record->title)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->description(fn(Invoice $record): string => $record->user?->userProfile?->phone)
                    ->searchable(),

                TextColumn::make('date')
                    ->date(fn() => 'd M Y')
                    ->description(fn(Invoice $record): string => $record->due_date ? 'Due: ' . $record->due_date->format('d M Y') : 'No Due Date'),

                TextColumn::make('total_price')
                    ->label('Total Price')
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
            ->defaultSort('serial_number', 'desc')
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->modalContent()
                        ->modalWidth('5xl'),
                    EditAction::make()
                        ->visible(fn(Invoice $record): bool => $record->status !== 'paid')
                        ->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()
                        ->visible(fn(Invoice $record): bool => $record->status !== 'paid')
                        ->icon('heroicon-o-trash'),
                ])
                    ->link()
                    ->label('Actions'),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code'];
    }
}
