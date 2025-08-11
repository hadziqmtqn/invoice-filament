<?php

namespace App\Filament\Resources;

use App\Enums\ItemUnit;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurringInvoiceStatus;
use App\Filament\Resources\RecurringInvoiceResource\Pages;
use App\Models\Item;
use App\Models\LineItem;
use App\Models\RecurringInvoice;
use App\Services\ItemService;
use App\Services\UserService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
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
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->tooltip(fn($record) => $record->invoice_number)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->searchable(),

                TextColumn::make('date')
                    ->description(fn($record) => 'Due: ' . ($record->due_date?->format('d M Y') ?? '-'))
                    ->date('d M Y'),

                TextColumn::make('recurrence_frequency')
                    ->badge()
                    ->color(fn ($state) => RecurrenceFrequency::tryFrom($state)?->getColor() ?? 'gray')
                    ->formatStateUsing(fn ($state, $record) => $record->repeat_every . ' ' . RecurrenceFrequency::tryFrom($state)?->label() ?? $state)
                    ->label('Repeat Every'),

                TextColumn::make('total_price')
                    ->money('idr'),

                TextColumn::make('status')
                    ->badge()
                    ->icon(fn($state) => RecurringInvoiceStatus::tryFrom($state)?->getIcon() ?? 'heroicon-o-question-mark-circle')
                    ->color(fn($state) => RecurringInvoiceStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->formatStateUsing(fn($state) => RecurringInvoiceStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(RecurringInvoiceStatus::options())
                    ->label('Status')
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                ])
            ])
            ->bulkActions([
                //
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
        return ['code'];
    }
}
