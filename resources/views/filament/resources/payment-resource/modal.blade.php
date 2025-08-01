@php
    use Illuminate\Support\Carbon;
@endphp

<div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow">
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
        <div>
            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400 tracking-wide mb-1">{{ $application?->name }}</div>
            <div class="text-sm text-gray-700 dark:text-gray-300">Faktur Pembayaran</div>
            <div class="text-xs text-gray-400 dark:text-gray-500">#{{ $payment->reference_number }}</div>
        </div>
        <div class="text-right">
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ Carbon::parse($payment->date)->format('d M Y') }}
            </div>
        </div>
    </div>
    <div class="mb-4">
        <div class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Penerima</div>
        <div class="text-gray-900 dark:text-gray-100">{{ $payment->user?->name ?? 'Pembeli' }}</div>
        <div class="text-gray-500 dark:text-gray-400 text-sm">{{ $payment->user?->email ?? '-' }}</div>
        <div class="text-gray-400 dark:text-gray-500 text-xs">{{ $payment->user?->userProfile?->street ?? '' }}</div>
    </div>

    <!-- CARD RINCIAN PEMBAYARAN -->
    <div class="mb-4 rounded-xl overflow-hidden shadow border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Rincian Pembayaran</div>
            <div class="text-gray-500 dark:text-gray-400 text-sm">Daftar item yang dibayar</div>
        </div>
        <div class="px-6 py-2">
            <table class="w-full table-auto">
                <thead>
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="py-2">Kode</th>
                    <th class="py-2">Item</th>
                    <th class="py-2">Qty</th>
                    <th class="py-2 text-right">Harga</th>
                </tr>
                </thead>
                <tbody>
                @foreach($payment->invoicePayments as $invoicePayment)
                    @foreach($invoicePayment->invoice?->invoiceItems as $item)
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <td class="py-2">{{ $invoicePayment->invoice?->code }}</td>
                            <td class="py-2">{{ $item->name }}</td>
                            <td class="py-2">{{ $item->qty }}</td>
                            <td class="py-2 text-right">
                                Rp {{ number_format($item->rate, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!-- END CARD -->

    <div class="mb-4">
        <div class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Metode Pembayaran</div>
        <div class="text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $payment->payment_method) ?? 'Tunai') }}</div>
    </div>

    @if($payment->payment_method === 'bank_transfer')
        <div class="mb-4">
            <div class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Bank Tujuan</div>
            <div class="text-gray-600 dark:text-gray-400">{{ $payment->bankAccount?->bank?->short_name }}</div>
        </div>
    @endif

    @if(isset($payment->note))
        <div class="mb-4">
            <div class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Catatan</div>
            <div class="text-gray-600 dark:text-gray-400">{{ $payment->note }}</div>
        </div>
    @endif
    <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="font-semibold text-lg text-gray-700 dark:text-gray-100">Total Pembayaran</div>
        <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
            Rp {{ number_format($payment->amount, 0, ',', '.') }}
        </div>
    </div>
    <div class="mt-6 text-center text-gray-400 dark:text-gray-500 text-xs">
        Terima kasih atas pembayaran Anda.<br>
        <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $application?->name }}</span> &copy; {{ date('Y') }}
    </div>
</div>