<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\UpdateInvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentObserver
{
    protected UpdateInvoiceService $updateInvoiceService;

    /**
     * @param UpdateInvoiceService $updateInvoiceService
     */
    public function __construct(UpdateInvoiceService $updateInvoiceService)
    {
        $this->updateInvoiceService = $updateInvoiceService;
    }

    public function creating(Payment $payment): void
    {
        $payment->slug = Str::uuid()->toString();
        $payment->serial_number = Payment::max('serial_number') + 1;
        $payment->reference_number = strtoupper('REF' . Str::random(6) . Str::padLeft($payment->serial_number, 6, '0'));
    }

    /**
     * @throws Throwable
     */
    public function saved(Payment $payment): void
    {
        $payment->refresh();

        DB::transaction(function () use ($payment) {
            $this->updateInvoiceService->updateInvoice($payment);
        });
    }
}
