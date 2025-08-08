<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PaymentChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var ?string
     */
    protected static ?string $chartId = 'paymentChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Payment Chart';

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
        // Ambil data total pembayaran per bulan, 12 bulan terakhir
        $payments = Payment::selectRaw('EXTRACT(MONTH FROM "date") as month, SUM(amount) as total')
            ->whereRaw('EXTRACT(YEAR FROM "date") = ?', [date('Y')])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->all();

        // Buat array bulan (Jan, Feb, dst)
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];

        // Siapkan data untuk chart (isi 0 jika tidak ada pembayaran di bulan tsb)
        $chartData = [];
        $categories = [];
        foreach ($months as $num => $name) {
            $categories[] = $name;
            $chartData[] = $payments[$num] ?? 0;
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 400,
            ],
            'series' => [
                [
                    'name' => 'Total Pembayaran',
                    'data' => $chartData,
                ],
            ],
            'xaxis' => [
                'categories' => $categories,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#f59e0b'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                ],
            ],
        ];
    }
}
