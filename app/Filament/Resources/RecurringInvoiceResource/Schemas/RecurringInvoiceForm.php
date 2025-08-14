<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Schemas;

use App\Enums\ItemUnit;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurringInvoiceStatus;
use App\Filament\Resources\ItemResource;
use App\Models\Item;
use App\Models\LineItem;
use App\Models\RecurringInvoice;
use App\Services\ItemService;
use App\Services\UserService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;

class RecurringInvoiceForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Group::make([
                    // TODO Main Data
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

                            TextInput::make('title')
                                ->placeholder('Enter the title of this invoice')
                                ->required()
                                ->columnSpanFull(),

                            DateTimePicker::make('date')
                                ->required()
                                ->default(now())
                                ->label('Invoice Date')
                                ->prefixIcon('heroicon-o-calendar')
                                ->native(false)
                                ->placeholder('Select a date')
                                ->closeOnDateSelection(),

                            DatePicker::make('due_date')
                                ->minDate(fn(Get $get) => $get('date'))
                                ->label('Due Date')
                                ->prefixIcon('heroicon-o-calendar')
                                ->native(false)
                                ->placeholder('Select a due date')
                                ->closeOnDateSelection(),
                        ]),

                    Section::make('Line Items')
                        ->schema([
                            Repeater::make('lineItems')
                                ->hiddenLabel()
                                ->relationship('lineItems')
                                ->schema([
                                    Select::make('item_id')
                                        ->label('Item')
                                        ->searchable()
                                        ->options(function (?LineItem $record) {
                                            return ItemService::dropdownOptions($record?->recurringInvoice?->lineItems?->pluck('item_id')->toArray() ?? []);
                                        })
                                        ->preload()
                                        ->required()
                                        ->createOptionForm(ItemResource::newItems())
                                        ->createOptionAction(fn(Action $action) => $action
                                            ->tooltip('Create a new item to add to this invoice')
                                            ->icon('heroicon-o-plus')
                                            ->color('primary')
                                            ->form(ItemResource::newItems())
                                            ->modalHeading('Create New Item')
                                            ->modalWidth('2xl')
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
                                                $qty = $get('qty') ?? 1; // Ambil qty jika ada, default 1
                                                $set('name', $item->name);
                                                $set('rate', $item->rate * $qty); // Kalikan rate dengan qty
                                                $set('description', $item->description);
                                                $set('unit', $item->unit);
                                            }
                                        })
                                        ->columnSpanFull(),

                                    Grid::make()
                                        ->columns()
                                        ->schema([
                                            TextInput::make('name')
                                                ->required()
                                                ->placeholder('Enter the item name'),

                                            TextInput::make('qty')
                                                ->numeric()
                                                ->required()
                                                ->placeholder('Enter the quantity')
                                                ->minValue(1)
                                                ->default(1)
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $itemId = $get('item_id');
                                                    $item = $itemId ? Item::find($itemId) : null;
                                                    $rate = $item ? $item->rate : 0;
                                                    $set('rate', $rate * ($state ?: 1));
                                                }),

                                            Select::make('unit')
                                                ->options(ItemUnit::options())
                                                ->placeholder('Enter the unit')
                                                ->reactive()
                                                ->native(false),

                                            TextInput::make('rate')
                                                ->label('Rate')
                                                ->numeric()
                                                ->required()
                                                ->placeholder('Enter the rate')
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
                                        ->rows(2)
                                        ->reactive()
                                        ->placeholder('Enter a description for this item')
                                        ->columnSpanFull(),
                                ])
                                ->deletable(function (callable $get) {
                                    // Ambil lineItems, pastikan array
                                    $items = $get('lineItems') ?? [];
                                    $itemsCount = is_array($items) ? count($items) : 0;

                                    return $itemsCount > 1;
                                })
                        ]),

                    // TODO Note
                    Section::make('Note')
                        ->schema([
                            Textarea::make('note')
                                ->hiddenLabel()
                                ->placeholder('Enter a note for this invoice')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpan(['lg' => 2]),

                Group::make([
                    // TODO Recurrance Data
                    Section::make()
                        ->schema([
                            Select::make('recurrence_frequency')
                                ->options(RecurrenceFrequency::options())
                                ->required()
                                ->native(false),

                            TextInput::make('repeat_every')
                                ->required()
                                ->integer()
                                ->default(1)
                                ->placeholder('Enter the number of times to repeat'),
                        ]),

                    // TODO Summary
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
                                        ->content(function (Get $get) {
                                            $items = $get('lineItems') ?? [];
                                            $total = array_reduce($items, function ($carry, $item) {
                                                $rate = isset($item['rate']) ? (int) $item['rate'] : 0;
                                                return $carry + $rate;
                                            }, 0);

                                            return (new HtmlString('<div style="font-size: 15pt"><strong>Rp' . number_format($total, 0, ',', '.') . '</strong></div>'));
                                        })
                                        ->columnSpanFull(),

                                    Placeholder::make('final_price')
                                        ->label('Total Price After Discount')
                                        ->content(function (Get $get) {
                                            $items = $get('lineItems') ?? [];
                                            $total = array_reduce($items, function ($carry, $item) {
                                                $rate = isset($item['rate']) ? (int) $item['rate'] : 0;
                                                return $carry + $rate;
                                            }, 0);
                                            $discount = (float) ($get('discount') ?? 0);
                                            $final = $total - ($discount / 100 * $total);

                                            return (new HtmlString('<div style="font-size: 15pt; color: #00bb00"><b>Rp' . number_format($final, 0, ',', '.') . '</b></div>'));
                                        })
                                        ->columnSpanFull(),
                                ])
                        ]),

                    Section::make('Status')
                        ->schema([
                            ToggleButtons::make('status')
                                ->hiddenLabel()
                                ->options(RecurringInvoiceStatus::options())
                                ->default(RecurringInvoiceStatus::DRAFT->value)
                                ->colors(RecurringInvoiceStatus::colors())
                                ->required()
                                ->inline()
                        ]),

                    // TODO Manufacturing Time
                    Section::make()
                        ->schema([
                            Placeholder::make('created_at')
                                ->label('Created Date')
                                ->content(fn(?RecurringInvoice $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                            Placeholder::make('updated_at')
                                ->label('Last Modified Date')
                                ->content(fn(?RecurringInvoice $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                        ])
                        ->visible(fn(?RecurringInvoice $record): bool => $record?->exists ?? false)
                ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
