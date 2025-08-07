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
    .status-paid {
        background: #e6fffa;
        color: #059669;
        padding: 6px 16px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
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
        <div class="invoice-title">#INV-20250805</div>
    </div>

    <div class="invoice-info">
        <div class="info-block">
            <strong>Billed To:</strong><br>
            John Doe<br>
            johndoe@email.com<br>
            123 Main Street, City
        </div>
        <div class="info-block">
            <strong>Issued By:</strong><br>
            Modern Company<br>
            support@moderncompany.com<br>
            456 Business Ave, Metropolis
        </div>
    </div>

    <div class="invoice-details">
        <table class="invoice-details-table">
            <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Web Design</td>
                <td>1</td>
                <td>$800</td>
                <td>$800</td>
            </tr>
            <tr>
                <td>SEO Optimization</td>
                <td>1</td>
                <td>$350</td>
                <td>$350</td>
            </tr>
            <tr>
                <td>Hosting (6 months)</td>
                <td>1</td>
                <td>$90</td>
                <td>$90</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td>$1,240</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="info-block" style="margin-bottom:12px;">
        <strong>Invoice Date:</strong> 2025-08-05<br>
        <strong>Due Date:</strong> 2025-08-20<br>
    </div>
    <div class="info-block">
        <span class="status-paid">Paid</span>
    </div>

    <div class="footer">
        Thank you for your business!
    </div>
</div>