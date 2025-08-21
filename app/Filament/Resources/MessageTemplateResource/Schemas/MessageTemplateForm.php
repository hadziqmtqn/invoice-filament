<?php

namespace App\Filament\Resources\MessageTemplateResource\Schemas;

use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class MessageTemplateForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('message_template_category_id')
                    ->label('Category')
                    ->options(fn() => MessageTemplateCategory::pluck('name', 'id')->toArray())
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn($state, callable $set) => $set('placeholder_category', MessageTemplateCategory::find($state)?->placeholder ?? ''))
                    ->afterStateHydrated(fn($state, callable $set) => $set('placeholder_category', MessageTemplateCategory::find($state)?->placeholder ?? '')),

                TextInput::make('title')
                    ->required()
                    ->placeholder('Enter title')
                    ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state))),

                Textarea::make('message')
                    ->required()
                    ->autosize()
                    ->placeholder('Enter message')
                    ->columnSpanFull(),

                ToggleButtons::make('is_active')
                    ->boolean()
                    ->inline()
                    ->columnSpanFull()
                    ->visible(fn(?MessageTemplate $record): bool => $record?->exists ?? false),

                Section::make('Placeholder')
                    ->description('Anda dapat menggunakan placeholder berikut dalam template pesan untuk menyisipkan informasi dinamis. Contoh: [Nama], [Email]')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('placeholder_category')
                            ->hiddenLabel()
                            ->reactive()
                            ->content(fn ($get) => new HtmlString(Str::markdown($get('placeholder_category') ?? ''))),
                    ]),
            ]);
    }
}
