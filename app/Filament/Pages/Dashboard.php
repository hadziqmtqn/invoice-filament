<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $slug = 'dashboard';

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetFilters')
                ->color('danger')
                ->action(fn () => $this->filters = [])
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->fillForm([])
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(3)
                    ->schema([
                        Select::make('productType')
                            ->native(false)
                            ->options([
                                'goods' => 'Goods',
                                'service' => 'Service',
                            ])
                            ->selectablePlaceholder(false)
                            ->reactive(),

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
                    ]),
            ])
            ->columns(1)
            ->statePath('filters');
    }
}
