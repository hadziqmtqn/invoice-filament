<?php

namespace App\Filament\Resources\ItemResource\Schemas;

use App\Enums\ItemUnit;
use App\Enums\ProductType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;

class ItemForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::itemForm());
    }

    public static function itemForm(): array
    {
        return [
            ToggleButtons::make('product_type')
                ->label('Jenis Produk')
                ->required()
                ->options(ProductType::options())
                ->colors(ProductType::colors())
                ->default('service')
                ->inline()
                ->columnSpanFull(),

            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->placeholder('Masukkan Nama Produk')
                ->hintIcon('heroicon-o-information-circle', 'Nama item yang muncul pada faktur.'),

            TextInput::make('item_name')
                ->label('Nama Opsional Produk')
                ->required()
                ->placeholder('Nama opsional dari produk ini')
                ->hintIcon('heroicon-o-information-circle', 'Nama item lain sebagai alternatif atau alias dari nama item utama.'),

            Select::make('unit')
                ->options(ItemUnit::options())
                ->native(false),

            TextInput::make('rate')
                ->label('Harga Satuan')
                ->required()
                ->numeric()
                ->minValue(10000)
                ->placeholder('Masukkan Harga Satuan'),

            Textarea::make('description')
                ->label('Deskripsi')
                ->maxLength(500)
                ->autosize()
                ->columnSpanFull()
                ->placeholder('Opsional, dapat digunakan untuk memberikan informasi tambahan tentang item tersebut.'),
        ];
    }
}
