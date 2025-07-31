<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Item;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
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
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
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
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->native(false)
                    ->columnSpanFull(),

                DatePicker::make('date')
                    ->required()
                    ->native(false)
                    ->default(now()),

                DatePicker::make('due_date')
                    ->required()
                    ->native(false)
                    ->minDate(fn(Get $get) => $get('date')),

                Repeater::make('invoiceItems')
                    ->relationship('invoiceItems')
                    ->schema([
                        Select::make('item_id')
                            ->label('Item')
                            ->relationship('item', 'name')
                            ->searchable()
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
                            ->columns()
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

                Textarea::make('note')
                    ->rows(3)
                    ->columnSpanFull(),

                Section::make('Total')
                    ->description('Total harga akan dihitung berdasarkan item yang ditambahkan.')
                    ->aside()
                    ->schema([
                        TextInput::make('discount')
                            ->label('Discount (%)')
                            ->helperText('Masukkan diskon dalam persen, misal: 10 untuk 10%')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->suffix('%'),

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

                                return (new HtmlString('<div style="font-size: 17pt"><b>Rp' . number_format($final, 0, ',', '.') . '</b></div>'));
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),


                Grid::make()
                    ->columns()
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created Date')
                            ->visible(fn(?Invoice $record): bool => $record?->exists ?? false)
                            ->content(fn(?Invoice $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Last Modified Date')
                            ->visible(fn(?Invoice $record): bool => $record?->exists ?? false)
                            ->content(fn(?Invoice $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->tooltip(fn($record): string => $record->invoice_number)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->description(fn(Invoice $record): string => $record->user?->userProfile?->phone)
                    ->searchable(),

                TextColumn::make('date')
                    ->date(fn() => 'd M Y')
                    ->description(fn(Invoice $record): string => $record->due_date ? 'Due: ' . $record->due_date->format('d M Y') : 'No Due Date'),

                TextColumn::make('discount')
                    ->label('Discount (%)')
                    ->numeric()
                    ->suffix('%'),

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
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('serial_number', 'desc')
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
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
