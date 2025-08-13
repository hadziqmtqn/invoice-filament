<?php

namespace App\Observers;

use App\Models\Invoice;

class InvoiceObserver
{
    public function saving(Invoice $invoice): void
    {
        // Hitung total invoice dan total pembayaran
        $totalInvoice = $invoice->invoiceItems->sum(function ($item) {
            return $item->rate * $item->qty;
        });
        $totalPaid = $invoice->invoicePayments->sum('amount_applied');

        if ($totalPaid >= $totalInvoice && $totalInvoice > 0) {
            // Lunas kapan pun, status "paid"
            $invoice->status = 'paid';
        } elseif (now()->gt($invoice->due_date)) {
            // Sudah jatuh tempo dan belum lunas
            $invoice->status = 'overdue';
        } elseif ($totalPaid > 0) {
            // Belum jatuh tempo, sudah ada pembayaran sebagian
            $invoice->status = 'partially_paid';
        } else {
            // Belum jatuh tempo, belum ada pembayaran
            $invoice->status = 'unpaid';
        }
    }
}