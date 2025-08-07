<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(3)
                    ->schema([
                        DatePicker::make('startDate')
                            ->closeOnDateSelection()
                            ->reactive()
                            ->prefixIcon('heroicon-o-calendar')
                            ->afterStateUpdated(fn (callable $get, callable $set) => $set('endDate', null))
                            ->native(false),

                        DatePicker::make('endDate')
                            ->closeOnDateSelection()
                            ->reactive()
                            ->prefixIcon('heroicon-o-calendar')
                            ->minDate(fn (callable $get) => $get('startDate'))
                            ->native(false),
                        // ...
                    ]),
            ])
            ->columns(1)
            ->statePath('filters');
    }
}
