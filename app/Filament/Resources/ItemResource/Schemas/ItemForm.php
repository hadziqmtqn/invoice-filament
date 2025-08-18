<?php

namespace App\Filament\Resources\ItemResource\Schemas;

use App\Enums\ItemUnit;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class ItemForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('product_type')
                    ->required()
                    ->options([
                        'goods' => 'Barang',
                        'service' => 'Jasa',
                    ])
                    ->default('service')
                    ->inline()
                    ->columnSpanFull(),

                TextInput::make('name')
                    ->required()
                    ->hintIcon('heroicon-o-information-circle', 'Nama item yang muncul pada faktur.'),

                TextInput::make('item_name')
                    ->label('Item Name Optional')
                    ->required()
                    ->hintIcon('heroicon-o-information-circle', 'Nama item lain sebagai alternatif atau alias dari nama item utama.'),

                Select::make('unit')
                    ->options(ItemUnit::options())
                    ->native(false),

                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->minValue(10000),

                Textarea::make('description')
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->helperText('Optional, can be used to provide additional information about the item.'),

                // hanya muncul di halaman edit
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('items')
                    ->image()
                    ->disk('s3')
                    ->maxSize(1024) // 1 MB
                    ->label('Image')
                    ->visibleOn('edit')
                    ->visibility('private')
                    ->columnSpanFull()
                    ->helperText('Optional, can be used to upload an image of the item.'),
            ]);
    }
}
