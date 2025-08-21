<?php

namespace App\Filament\Pages;

use App\Enums\ProductType;
use CodeWithKyrian\FilamentDateRange\Forms\Components\DateRangePicker;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $slug = 'dashboard';
    protected static ?string $navigationLabel = 'Dasbor';
    protected static ?string $navigationGroup = 'Main';

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetFilters')
                ->label('Ulangi Filter')
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
                        Grid::make()
                            ->schema([
                                DateRangePicker::make('dateRange')
                                    ->label('Rentang Tanggal')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->columnSpan(['lg' => 2]),

                        Select::make('productType')
                            ->label('Jenis Produk')
                            ->native(false)
                            ->options(ProductType::options())
                            ->selectablePlaceholder(false)
                            ->reactive()
                            ->columnSpan(['lg' => 1]),
                    ]),
            ])
            ->columns(1)
            ->statePath('filters');
    }
}
