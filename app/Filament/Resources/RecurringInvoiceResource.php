<?php

namespace App\Filament\Resources;

use App\Enums\ItemUnit;
use App\Enums\RecurrenceFrequency;
use App\Filament\Resources\RecurringInvoiceResource\Pages;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\RecurringInvoice;
use App\Services\ItemService;
use App\Services\UserService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
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
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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

                            DatePicker::make('date')
                                ->required()
                                ->default(now())
                                ->label('Invoice Date')
                                ->prefixIcon('heroicon-o-calendar')
                                ->native(false)
                                ->placeholder('Select a date')
                                ->closeOnDateSelection(),

                            DatePicker::make('due_date')
                                ->required()
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
                                        ->options(function (?InvoiceItem $record) {
                                            return ItemService::dropdownOptions($record?->invoice?->invoiceItems?->pluck('item_id') ?? []);
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
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (!$state) {
                                                // kosongkan jika tidak ada item
                                                $set('rate', null);
                                                $set('description', null);
                                                $set('unit', null);
                                                return;
                                            }

                                            $item = Item::find($state);
                                            if ($item) {
                                                $set('rate', $item->rate);
                                                $set('description', $item->description);
                                                $set('unit', $item->unit);
                                            }
                                        })
                                        ->columnSpanFull(),

                                    Grid::make()
                                        ->columns(3)
                                        ->schema([
                                            TextInput::make('qty')
                                                ->numeric()
                                                ->required()
                                                ->placeholder('Enter the quantity')
                                                ->minValue(1)
                                                ->default(1)
                                                ->reactive(),

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
                                        ->label('Total Price After Discount')
                                        ->content(function (Get $get) {
                                            $items = $get('lineItems') ?? [];
                                            $total = 0;
                                            foreach ($items as $item) {
                                                $qty = isset($item['qty']) ? (int) $item['qty'] : 0;
                                                $rate = isset($item['rate']) ? (int) $item['rate'] : 0;
                                                $total += $qty * $rate;
                                            }
                                            $discount = (float) ($get('discount') ?? 0);
                                            $final = $total - ($discount / 100 * $total);

                                            return (new HtmlString('<div style="font-size: 15pt; color: #00bb00"><b>Rp' . number_format($final, 0, ',', '.') . '</b></div>'));
                                        })
                                        ->columnSpanFull(),
                                ])
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
