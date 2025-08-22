<?php

namespace App\Services;

use App\Enums\DataStatus;
use App\Models\Invoice;
use App\Models\Payment;

class UpdateInvoiceService
{
    public function updateInvoice(Payment $payment): void
    {
        if ($payment->status == DataStatus::PAID->value) {
            $invoicePayments = $payment->invoicePayments;

            foreach ($invoicePayments as $invoicePayment) {
                $invoice = $invoicePayment->invoice;

                $this->updateStatusInvoice($invoice);
                $invoice->save();
            }
        }
    }

    public function updateStatusInvoice(Invoice $invoice): void
    {
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
