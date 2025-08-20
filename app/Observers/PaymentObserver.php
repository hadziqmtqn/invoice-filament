<?php

namespace App\Observers;

use App\Enums\DataStatus;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentObserver
{
    public function creating(Payment $payment): void
    {
        $payment->slug = Str::uuid()->toString();
        $payment->serial_number = Payment::max('serial_number') + 1;
        $payment->reference_number = strtoupper('REF' . Str::random(6) . Str::padLeft($payment->serial_number, 6, '0'));
    }

    public function updating(Payment $payment): void
    {
        if ($payment->status === DataStatus::PAID->value) {
            \Log::info('Cek payment pertama kali dibuat');
            $invoicePayments = $payment->invoicePayments;

            foreach ($invoicePayments as $invoicePayment) {
                $invoice = $invoicePayment->invoice;

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
    }
}
