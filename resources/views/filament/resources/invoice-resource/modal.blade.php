@php
    $status = $invoice->status;
    $statusColor = [
        'paid' => 'text-primary-600 border-primary-400',
        'unpaid' => 'text-danger-600 border-danger-400',
    ][$status] ?? 'text-warning-600 border-warning-400';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Kolom Kiri: detail -->
    <dl class="space-y-2">
        <div class="flex items-center gap-x-4">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 w-32">Code</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $invoice->code }}</dd>
        </div>
        <div class="flex items-center gap-x-4">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 w-32">Name</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $invoice->user?->name }}</dd>
        </div>
        <div class="flex items-center gap-x-4">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 w-32">Phone</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $invoice->user?->userProfile?->phone }}</dd>
        </div>
    </dl>

    <!-- Kolom Kanan: status -->
    <div class="flex items-center justify-center h-full">
        <div class="border px-6 py-4 rounded-lg font-semibold text-lg {{ $statusColor }}">
            {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
        </div>
    </div>
</div>

<div class="mb-4 rounded-xl overflow-hidden shadow border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Rincian</div>
        {{--<div class="text-gray-500 dark:text-gray-400 text-sm">Daftar item yang dibayar</div>--}}
    </div>
    <div class="px-6 py-2">
        <table class="w-full table-auto">
            <thead>
            <tr class="text-left text-gray-500 dark:text-gray-300">
                <th class="py-2">Item</th>
                <th class="py-2">Qty</th>
                <th class="py-2 text-right">Harga</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->invoiceItems as $item)
                <tr class="border-b border-gray-100 dark:border-gray-700">
                    <td class="py-2">{{ $item->name }}</td>
                    <td class="py-2">{{ $item->qty }}</td>
                    <td class="py-2 text-right">
                        Rp {{ number_format($item->rate, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr class="border-t border-gray-200 dark:border-gray-700">
                <td colspan="2" class="py-2 font-semibold text-gray-700 dark:text-gray-200 text-right">Subtotal</td>
                <td class="py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                    Rp {{ number_format($invoice->total_price, 0, ',', '.') }}
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>