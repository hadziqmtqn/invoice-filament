@php
use Illuminate\Support\Carbon;
@endphp

<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f6f6f6;
        margin: 0;
        padding: 0;
    }
    .invoice-box {
        background: #fff;
        max-width: 700px;
        margin: 30px auto;
        padding: 40px 50px;
        border-radius: 8px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.06);
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #eee;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    .company-logo {
        font-weight: bold;
        font-size: 2rem;
        color: #1e88e5;
        letter-spacing: 2px;
    }
    .invoice-title {
        font-size: 1.5rem;
        color: #333;
    }
    .invoice-details, .customer-details {
        margin-bottom: 25px;
    }
    .details-table {
        width: 100%;
        margin-bottom: 20px;
    }
    .details-table td {
        padding: 4px 0;
    }
    .amount-box {
        margin-top: 30px;
        text-align: right;
    }
    .amount-label {
        font-size: 1.1rem;
        color: #666;
    }
    .amount-value {
        font-size: 2rem;
        font-weight: bold;
        color: #16a34a;
    }
    .footer {
        text-align: center;
        color: #aaa;
        margin-top: 40px;
        font-size: 0.9rem;
    }
    .badge {
        display: inline-block;
        padding: 6px 18px;
        border-radius: 18px;
        font-size: 0.95rem;
        color: #fff;
        background: #6366f1;
    }
</style>
<div class="invoice-box">
    <div class="invoice-header">
        <div class="company-logo">
            INVOICELY
        </div>
        <div class="invoice-title">
            Faktur Pembayaran
            <div style="font-size:0.95rem; color:#888;">#{{ $payment->id }}</div>
        </div>
    </div>
    <div class="invoice-details">
        <table class="details-table">
            <tr>
                <td><strong>Tanggal:</strong></td>
                <td>{{ Carbon::parse($payment->created_at)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Metode Pembayaran:</strong></td>
                <td>{{ ucfirst($payment->payment_method ?? 'Tunai') }}</td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="badge" style="background:#16a34a;">Lunas</span>
                </td>
            </tr>
        </table>
    </div>
    <div class="customer-details">
        <strong>Penerima:</strong>
        <div>{{ $payment->user?->name ?? 'Pembeli' }}</div>
        <div>{{ $payment->user?->email ?? '-' }}</div>
        <div>{{ $payment->user?->userProfile?->street ?? '' }}</div>
    </div>

    @if(isset($payment->note))
        <div style="margin-bottom: 15px;">
            <strong>Catatan:</strong>
            <div>{{ $payment->note }}</div>
        </div>
    @endif

    <div class="amount-box">
        <div class="amount-label">Total Pembayaran</div>
        <div class="amount-value">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
    </div>

    <div class="footer">
        Terima kasih atas pembayaran Anda.<br>
        <span style="color:#1e88e5;">INVOICELY</span> &copy; {{ date('Y') }}
    </div>
</div>