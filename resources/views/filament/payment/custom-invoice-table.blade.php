<table class="table-auto w-full">
    <thead>
    <tr>
        <th>Pilih</th>
        <th>Nomor Invoice</th>
        <th>Outstanding</th>
        <th>Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($invoices as $invoice)
        <tr>
            <td>
                <input type="checkbox" name="checked_invoices[]" value="{{ $invoice->id }}" />
            </td>
            <td>{{ $invoice->code }}</td>
            <td>Rp {{ number_format($invoice->invoiceItems->sum('rate') - $invoice->invoicePayments->sum('amount_applied'), 0, ',', '.') }}</td>
            <td>
                <input type="number" name="amount_invoice[{{ $invoice->id }}]" min="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" />
            </td>
        </tr>
    @endforeach
    </tbody>
</table>