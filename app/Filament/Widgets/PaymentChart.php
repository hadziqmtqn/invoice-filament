<?php

namespace App\Filament\Widgets;

use App\Enums\Months;
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
        $userRole = auth()->user()->hasRole('user');

        // Ambil data total pembayaran per bulan, 12 bulan terakhir
        $payments = Payment::selectRaw('EXTRACT(MONTH FROM "date") as month, SUM(amount) as total')
            ->whereRaw('EXTRACT(YEAR FROM "date") = ?', [date('Y')])
            ->when($userRole, function ($query) {
                return $query->where('user_id', auth()->id());
            })
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->all();

        // Buat array bulan (Jan, Feb, dst)
        $months = Months::all();

        // Siapkan data untuk chart (isi 0 jika tidak ada pembayaran di bulan tsb)
        $chartData = [];
        $categories = [];
        foreach ($months as $month) {
            $categories[] = $month->short();
            $chartData[] = $payments[$month->value] ?? 0;
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400,
                'toolbar' => [
                    'show' => false,
                ]
            ],
            'series' => [
                [
                    'name' => 'Total',
                    'data' => $chartData,
                ],
            ],
            'xaxis' => [
                'categories' => $categories,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'poppins',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'poppins',
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
