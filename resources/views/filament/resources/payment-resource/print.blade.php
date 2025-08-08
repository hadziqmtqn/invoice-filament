@php
    use Illuminate\Support\Carbon;
@endphp

<style>
    .pdf-invoice-container {
        max-width: 650px;
        margin: 2rem auto;
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 2px 16px rgba(0,0,0,0.07);
        padding: 2.5rem;
    }
    .pdf-invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .pdf-invoice-brand {
        font-size: 2rem;
        font-weight: bold;
        color: #2563eb;
        letter-spacing: 2px;
        margin-bottom: .25rem;
    }
    .pdf-invoice-subtitle {
        font-size: 1rem;
        color: #374151;
        margin-bottom: .15rem;
    }
    .pdf-invoice-ref-number {
        font-size: .85rem;
        color: #6b7280;
        margin-bottom: 0;
    }
    .pdf-invoice-date {
        font-size: .85rem;
        color: #6b7280;
        text-align: right;
        margin-top: .75rem;
    }
    .pdf-invoice-section-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: .25rem;
        font-size: 1rem;
    }
    .pdf-invoice-section-value {
        color: #111827;
    }
    .pdf-invoice-section-value-sm {
        color: #6b7280;
        font-size: .95rem;
    }
    .pdf-invoice-section-value-xs {
        color: #9ca3af;
        font-size: .85rem;
    }
    .pdf-invoice-card {
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        border-radius: 1rem;
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .pdf-invoice-card-header {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
    }
    .pdf-invoice-card-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: .15rem;
    }
    .pdf-invoice-card-subtitle {
        color: #6b7280;
        font-size: .95rem;
    }
    .pdf-invoice-card-body {
        padding: .5rem 1.5rem 1rem 1.5rem;
    }
    .pdf-invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    .pdf-invoice-table th, .pdf-invoice-table td {
        padding: .5rem .25rem;
        text-align: left;
    }
    .pdf-invoice-table th {
        color: #374151;
        font-size: .95rem;
    }
    .pdf-invoice-table tr {
        border-bottom: 1px solid #f3f4f6;
    }

    .pdf-invoice-total-row {
        border-top: 2px solid #e5e7eb;
    }
    .pdf-invoice-total-label {
        font-weight: 600;
        font-size: 1.1rem;
        color: #374151;
    }
    .pdf-invoice-total-value {
        font-weight: 700;
        font-size: 1.4rem;
        color: #2563eb;
    }
    .pdf-invoice-footer {
        text-align: center;
        color: #9ca3af;
        margin-top: 2.5rem;
        font-size: .95rem;
    }
    .pdf-invoice-brand-footer {
        color: #2563eb;
        font-weight: bold;
    }
    /* For notes/catatan */
    .pdf-invoice-note-block {
        margin-bottom: 1.2rem;
    }
    .pdf-invoice-note-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: .2rem;
    }
    .pdf-invoice-note-content {
        color: #6b7280;
    }
</style>

<div class="pdf-invoice-container">
    <div class="pdf-invoice-header">
        <div>
            <div class="pdf-invoice-brand">{{ $application?->name }}</div>
            <div class="pdf-invoice-subtitle">Faktur Pembayaran</div>
            <div class="pdf-invoice-ref-number">#{{ $payment->reference_number }}</div>
        </div>
        <div>
            <div class="pdf-invoice-date">
                {{ Carbon::parse($payment->date)->format('d M Y') }}
            </div>
        </div>
    </div>
    <div style="margin-bottom:1.2rem;">
        <div class="pdf-invoice-section-title">Penerima</div>
        <div class="pdf-invoice-section-value">{{ $payment->user?->name ?? 'Pembeli' }}</div>
        <div class="pdf-invoice-section-value-sm">{{ $payment->user?->userProfile?->phone ?? '-' }}</div>
        <div class="pdf-invoice-section-value-xs">{{ $payment->user?->userProfile?->street ?? '' }}</div>
    </div>

    <!-- CARD RINCIAN PEMBAYARAN -->
    <div class="pdf-invoice-card">
        <div class="pdf-invoice-card-header">
            <div class="pdf-invoice-card-title">Rincian Pembayaran</div>
            <div class="pdf-invoice-card-subtitle">Daftar item yang dibayar</div>
        </div>
        <div class="pdf-invoice-card-body">
            <table class="pdf-invoice-table">
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th style="text-align: right">Harga</th>
                </tr>
                </thead>
                <tbody>
                @foreach($payment->invoicePayments as $invoicePayment)
                    @foreach($invoicePayment->invoice?->invoiceItems as $item)
                        <tr>
                            <td>{{ $invoicePayment->invoice?->code }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->qty }}</td>
                            <td style="text-align: right">
                                Rp {{ number_format($item->rate, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right">Subtotal</td>
                    <th style="text-align: right">
                        Rp {{ number_format($payment->total_bill, 0, ',', '.') }}
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <!-- END CARD -->

    <div style="margin-bottom:1.2rem;">
        <div class="pdf-invoice-section-title">Metode Pembayaran</div>
        <div class="pdf-invoice-section-value-sm">{{ ucfirst(str_replace('_', ' ', $payment->payment_method) ?? 'Tunai') }}</div>
    </div>

    @if($payment->payment_method === 'bank_transfer')
        <div style="margin-bottom:1.2rem;">
            <div class="pdf-invoice-section-title">Bank Tujuan</div>
            <div class="pdf-invoice-section-value-sm">{{ $payment->bankAccount?->bank?->short_name }}</div>
        </div>
    @endif

    @if(isset($payment->note))
        <div class="pdf-invoice-note-block">
            <div class="pdf-invoice-note-title">Catatan</div>
            <div class="pdf-invoice-note-content">{{ $payment->note }}</div>
        </div>
    @endif
    <div class="pdf-invoice-total-row" style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem; padding-top:1rem;">
        <div class="pdf-invoice-total-label">Total Pembayaran</div>
        <div class="pdf-invoice-total-value">
            Rp {{ number_format($payment->amount, 0, ',', '.') }}
        </div>
    </div>
    <div class="pdf-invoice-footer">
        Terima kasih atas pembayaran Anda.<br>
        <span class="pdf-invoice-brand-footer">{{ $application?->name }}</span> &copy; {{ date('Y') }}
    </div>
</div>