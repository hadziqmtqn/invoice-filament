<style>
    .invoice-container {
        background: #fff;
        max-width: 750px;
        margin: auto;
        border-radius: 14px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        padding: 32px;
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 18px;
        margin-bottom: 28px;
    }
    .invoice-title {
        font-size: 1rem;
        font-weight: 700;
        color: #374151;
    }
    .invoice-logo {
        height: 60px;
        object-fit: contain;
        border-radius: 8px;
    }
    .invoice-details {
        margin-bottom: 34px;
    }
    .invoice-details-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 32px;
    }
    .invoice-details-table th,
    .invoice-details-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }
    .invoice-details-table th {
        background: #edf2f7;
        color: #374151;
        font-weight: 600;
    }
    .invoice-details-table td {
        color: #4b5563;
    }
    .total-row td {
        font-weight: bold;
        color: #111827;
        border-bottom: none;
    }
    .invoice-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 26px;
    }
    .info-block {
        font-size: 0.97rem;
        color: #6b7280;
    }
    .status-badge {
        padding: 6px 16px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
    }
    .status-paid {
        background: #e6fffa;
        color: #059669;
    }
    .status-partially-paid {
        background: #fffbeb;
        color: #d97706;
    }
    .status-overdue {
        background: #fef2f2;
        color: #dc2626;
    }
    .status-draft {
        background: #f3f4f6;
        color: #6b7280;
    }
    .footer {
        text-align: center;
        margin-top: 28px;
        color: #9ca3af;
        font-size: 0.93rem;
    }
</style>
<div class="invoice-container">
    <div class="invoice-header">
        <img src="{{ $application?->invoice_logo_asset }}" alt="Logo" class="invoice-logo">
        <div style="text-align: center">
            <div class="info-block">
                <span class="status-badge status-{{ str_replace('_', '-', $invoice->status) }}">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</span>
            </div>
            <div class="invoice-title">#{{ $invoice->code }}</div>
        </div>
    </div>

    <div class="invoice-info">
        <div class="info-block">
            <strong>Billed To:</strong><br>
            {{ $invoice->user?->name }}<br>
            {{ $invoice->user?->email }}<br>
            {{ $invoice->user?->userProfile?->street }}
        </div>
        <div class="info-block">
            <strong>Issued By:</strong><br>
            {{ $application?->name }}<br>
            {{ $application?->email }}<br>
        </div>
    </div>

    <div class="invoice-details">
        <table class="invoice-details-table">
            <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th style="text-align: right">Price</th>
                <th style="text-align: right">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->invoiceItems as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->qty }}</td>
                    <td style="text-align: right">Rp{{ number_format($item->rate,0,',','.') }}</td>
                    <td style="text-align: right">Rp{{ number_format(($item->qty * $item->rate),0,',','.') }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">Total</td>
                    <td style="text-align: right">Rp{{ number_format($invoice->total_price,0,',','.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="info-block" style="margin-bottom:12px;">
        <strong>Invoice Date:</strong> {{ Carbon\Carbon::parse($invoice->date)->isoFormat('DD MMMM Y') }}<br>
        <strong>Due Date:</strong> {{ Carbon\Carbon::parse($invoice->due_date)->isoFormat('DD MMMM Y') }}<br>
    </div>

    <div class="footer">
        Thank you for your business!
    </div>
</div>