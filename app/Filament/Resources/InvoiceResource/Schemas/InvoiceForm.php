<?php

namespace App\Filament\Resources\InvoiceResource\Schemas;

use App\Enums\ItemUnit;
use App\Filament\Resources\ItemResource;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Services\ItemService;
use App\Services\RecurringInvoiceService;
use App\Services\UserService;
use Filament\Forms\Components\Actions\Action;
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
use Illuminate\Support\HtmlString;

class InvoiceForm
{
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
                                    ->label('Pilih Pengguna')
                                    ->options(function (?Invoice $record) {
                                        return UserService::dropdownOptions($record?->exists ? $record->user_id : null);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, callable $set) {
                                        // Reset recurring_invoice_id when user changes
                                        $set('recurring_invoice_id', null);
                                    }),

                                Select::make('recurring_invoice_id')
                                    ->label('Faktur Berulang (Optional)')
                                    ->options(function(Get $get, ?Invoice $record) {
                                        return RecurringInvoiceService::selectOptions($get('user_id'), $record?->recurring_invoice_id);
                                    })
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->reactive(),

                                TextInput::make('title')
                                    ->label('Judul Faktur')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Masukkan Judul Faktur')
                                    ->columnSpanFull(),

                                DatePicker::make('date')
                                    ->label('Tanggal')
                                    ->required()
                                    ->format('d M Y')
                                    ->native(false)
                                    ->default(now())
                                    ->placeholder('Pilih Tanggal Faktur')
                                    ->closeOnDateSelection(),

                                DatePicker::make('due_date')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->required()
                                    ->format('d M Y')
                                    ->native(false)
                                    ->minDate(fn(Get $get) => $get('date'))
                                    ->placeholder('Pilih Tanggal Jatuh Tempo')
                                    ->closeOnDateSelection(),
                            ]),

                        Section::make('Item Faktur')
                            ->description('Tambahkan item yang akan ditagihkan dalam invoice ini.')
                            ->schema([
                                Repeater::make('invoiceItems')
                                    ->relationship('invoiceItems')
                                    ->hiddenLabel()
                                    ->schema([
                                        Select::make('item_id')
                                            ->label('Item')
                                            ->searchable()
                                            ->options(function (?InvoiceItem $record) {
                                                return ItemService::dropdownOptions($record?->invoice?->invoiceItems?->pluck('item_id')->toArray() ?? []);
                                            })
                                            ->preload()
                                            ->required()
                                            ->createOptionForm(ItemResource\Schemas\ItemForm::itemForm())
                                            ->createOptionAction(fn(Action $action) => $action
                                                ->tooltip('Tambah baru item faktur')
                                                ->icon('heroicon-o-plus')
                                                ->color('primary')
                                                ->form(ItemResource\Schemas\ItemForm::itemForm())
                                                ->modalHeading('Tambah Item Baru')
                                                ->modalWidth('2xl')
                                                ->slideOver()
                                            )
                                            ->createOptionUsing(function (array $data) {
                                                // Pastikan ini membuat dan menyimpan Item baru
                                                $item = Item::create($data);
                                                return $item->getKey();
                                            })
                                            ->reactive()
                                            ->native(false)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
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
                                                    // Set nilai berdasarkan item yang dipilih
                                                    $qty = $get('qty') ?? 1; // Ambil qty jika ada, default 1
                                                    $set('name', $item->name);
                                                    $set('rate', $item->rate * $qty); // Rate dikalikan qty
                                                    $set('description', $item->description);
                                                    $set('unit', $item->unit);
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Grid::make()
                                            ->columns()
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Nama Item')
                                                    ->required()
                                                    ->placeholder('Masukkan Nama Item'),

                                                TextInput::make('qty')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        // Update rate jika qty berubah
                                                        $itemId = $get('item_id');
                                                        $item = $itemId ? Item::find($itemId) : null;
                                                        $rate = $item ? $item->rate : 0;
                                                        $set('rate', $rate * ($state ?: 1));
                                                    })
                                                    ->reactive(),

                                                Select::make('unit')
                                                    ->options(ItemUnit::options())
                                                    ->reactive()
                                                    ->native(false),

                                                TextInput::make('rate')
                                                    ->label('Harga Jual')
                                                    ->numeric()
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateHydrated(function (callable $set, callable $get) {
                                                        $itemId = $get('item_id');
                                                        $item = $itemId ? Item::find($itemId) : null;
                                                        if ($item) {
                                                            $qty = $get('qty') ?: 1;
                                                            $set('rate', $item->rate * $qty);
                                                        }
                                                    })
                                                    ->readOnly(),
                                            ]),

                                        Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->rows(2)
                                            ->reactive()
                                            ->placeholder('Keterangan faktur')
                                            ->columnSpanFull(),
                                    ])
                                    ->minItems(1)
                                    ->deletable(function ($state, callable $get, $livewire) {
                                        $invoice = $livewire->record ?? null;

                                        // Ambil invoiceItems, pastikan array
                                        $items = $get('invoiceItems') ?? [];
                                        $itemsCount = is_array($items) ? count($items) : 0;

                                        if (!$invoice) {
                                            // Create mode, boleh hapus jika item > 1
                                            return $itemsCount > 1;
                                        }

                                        if ($invoice->invoicePayments()->exists()) {
                                            // Jika sudah ada pembayaran, tidak boleh hapus
                                            return false;
                                        }

                                        return $itemsCount > 1;
                                    })
                                    ->addable(function ($state, callable $get, $livewire) {
                                        $invoice = $livewire->record ?? null;

                                        if ($invoice?->invoicePayments()->exists()) {
                                            // Jika sudah ada pembayaran, tidak boleh tambah item
                                            return false;
                                        }

                                        return true;
                                    })
                                    ->columnSpanFull()
                                    ->addActionLabel('Tambah Item')
                                    ->columns(),
                            ]),

                        Section::make()
                            ->schema([
                                Textarea::make('note')
                                    ->rows(3)
                                    ->placeholder('Catatan untuk faktur ini'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Total')
                            ->description('Total harga akan dihitung berdasarkan item yang ditambahkan.')
                            ->schema([
                                TextInput::make('discount')
                                    ->label('Diskon (%)')
                                    ->helperText('Masukkan diskon dalam persen, misal: 10 untuk 10%')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    ->suffix('%'),

                                Grid::make()
                                    ->schema([
                                        Placeholder::make('total_price')
                                            ->label('Total Tagihan')
                                            ->content(function (Get $get) {
                                                $items = $get('invoiceItems') ?? [];
                                                $total = array_reduce($items, function ($carry, $item) {
                                                    $rate = isset($item['rate']) ? (int)$item['rate'] : 0;
                                                    return $carry + $rate;
                                                }, 0);

                                                return (new HtmlString('<div style="font-size: 15pt"><strong>Rp' . number_format($total, 0, ',', '.') . '</strong></div>'));
                                            })
                                            ->columnSpanFull(),

                                        Placeholder::make('final_price')
                                            ->label('Total Tagihan Setelah Diskon')
                                            ->content(function (Get $get) {
                                                $items = $get('invoiceItems') ?? [];
                                                $total = array_reduce($items, function ($carry, $item) {
                                                    $rate = isset($item['rate']) ? (int)$item['rate'] : 0;
                                                    return $carry + $rate;
                                                }, 0);

                                                $discount = (float)($get('discount') ?? 0);
                                                $final = $total - ($discount / 100 * $total);

                                                return (new HtmlString('<div style="font-size: 15pt; color: #00bb00"><b>Rp' . number_format($final, 0, ',', '.') . '</b></div>'));
                                            })
                                            ->columnSpanFull(),
                                    ])
                            ]),

                        Section::make('Rekening Bank')
                            ->description('Transfer pembayaran ke salah satu rekening berikut:')
                            ->schema([
                                Placeholder::make('bank_accounts')
                                    ->hiddenLabel()
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
                                            ->label('Dibuat Pada')
                                            ->content(fn(?Invoice $record): string => $record?->created_at?->diffForHumans() ?? '-')
                                            ->columnSpanFull(),

                                        Placeholder::make('updated_at')
                                            ->label('Terakhir diperbarui')
                                            ->content(fn(?Invoice $record): string => $record?->updated_at?->diffForHumans() ?? '-')
                                            ->columnSpanFull(),
                                    ])
                            ])
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
