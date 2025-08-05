<?php

namespace App\Observers;

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
        $totalPaid = $invoice->invoicePayments->sum('amount_applied');

        if ($totalPaid >= $totalInvoice) {
            $invoice->status = 'paid';
        } elseif ($totalPaid > 0) {
            $invoice->status = 'partially_paid';
        } else {
            $invoice->status = 'unpaid'; // atau status default kamu
        }

        $invoice->saveQuietly(); // Quietly untuk hindari infinite loop jika ada observer di Invoice
    }
}
