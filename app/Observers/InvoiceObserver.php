<?php

namespace App\Observers;

use App\Enums\DataStatus;
use App\Models\Invoice;

class InvoiceObserver
{
    public function updating(Invoice $invoice): void
    {
        // Hitung total invoice dan total pembayaran
        $totalInvoice = $invoice->invoiceItems->sum(function ($item) {
            return $item->rate * $item->qty;
        });

        $totalPaid = $invoice->invoicePayments()
            ->whereHas('payment', fn($query) => $query->filterByStatus(DataStatus::PAID->value))
            ->sum('amount_applied');

        if ($totalPaid >= $totalInvoice && $totalInvoice > 0) {
            // Lunas kapan pun, status "paid"
            $status = 'paid';
        } elseif (now()->gt($invoice->due_date)) {
            // Sudah jatuh tempo dan belum lunas
            $status = 'overdue';
        } elseif ($totalPaid > 0) {
            // Belum jatuh tempo, sudah ada pembayaran sebagian
            $status = 'partially_paid';
        } else {
            // Belum jatuh tempo, belum ada pembayaran
            $status = 'unpaid';
        }

        $invoice->status = $status;
    }
}