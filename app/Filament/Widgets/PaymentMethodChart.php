<?php

namespace App\Filament\Widgets;

use App\Enums\DataStatus;
use App\Models\Payment;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PaymentMethodChart extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * Chart Id
     *
     * @var ?string
     */
    protected static ?string $chartId = 'paymentMethodChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Statistik Metode Pembayaran';

    /**
     * @var string|null
     */
    protected static ?string $loadingIndicator = 'Loading...';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $startDate = $this->filters['dateRange']['start'] ?? null;
        $endDate = $this->filters['dateRange']['end'] ?? null;

        $userRole = auth()->user()->hasRole('user');

        $query = Payment::query()
            ->when($userRole, function ($query) {
                return $query->where('user_id', auth()->id());
            });

        if ($startDate) $query->whereDate('date', '>=', $startDate);
        if ($endDate) $query->whereDate('date', '<=', $endDate);

        // Ambil data jumlah transaksi per payment_method
        $payments = $query->selectRaw('payment_method, COUNT(*) as total')
            ->filterByStatus(DataStatus::PAID->value)
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->pluck('total', 'payment_method')
            ->all();

        $labels = array_map(
            fn($method) => ucwords(str_replace('_', ' ', $method)),
            array_keys($payments)
        );
        $series = array_values($payments);

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 400,
            ],
            'series' => $series,
            'labels' => $labels,
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
        ];
    }
}
