<?php

namespace App\Observers;

use App\Enums\DataStatus;
use App\Models\Invoice;
use App\Models\InvoicePayment;

class InvoicePaymentObserver
{
    public function saved(InvoicePayment $invoicePayment): void
    {
        // Update status invoice setelah pembayaran disimpan
        self::updateInvoiceStatus($invoicePayment->invoice_id);
    }

    public function deleted(InvoicePayment $invoicePayment): void
    {
        // Update status invoice setelah pembayaran dihapus
        self::updateInvoiceStatus($invoicePayment->invoice_id);
    }

    public static function updateInvoiceStatus($invoiceId): void
    {
        $invoice = Invoice::with(['invoiceItems', 'invoicePayments'])
            ->find($invoiceId);
        if (!$invoice) return;

        $totalInvoice = $invoice->invoiceItems->sum(function ($item) {
            return $item->rate * $item->qty; // Asumsi ada quantity di invoice items
        });
        $totalPaid = $invoice->invoicePayments()
            ->whereHas('payment', fn($query) => $query->filterByStatus(DataStatus::PAID->value))
            ->sum('amount_applied');

        /*if ($totalPaid >= $totalInvoice && $totalInvoice > 0) {
            $invoice->status = 'paid';
        } elseif ($totalPaid > 0) {
            $invoice->status = 'partially_paid';
        } else {
            $invoice->status = 'unpaid'; // atau status default kamu
        }

        $invoice->saveQuietly();*/ // Quietly untuk hindari infinite loop jika ada observer di Invoice
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
        $invoice->saveQuietly();
    }
}
