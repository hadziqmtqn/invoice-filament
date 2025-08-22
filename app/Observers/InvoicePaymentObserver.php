<?php

namespace App\Observers;

use App\Models\InvoicePayment;
use App\Services\UpdateInvoiceService;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvoicePaymentObserver
{
    protected UpdateInvoiceService $updateInvoiceService;

    /**
     * @param UpdateInvoiceService $updateInvoiceService
     */
    public function __construct(UpdateInvoiceService $updateInvoiceService)
    {
        $this->updateInvoiceService = $updateInvoiceService;
    }

    /**
     * @throws Throwable
     */
    public function saved(InvoicePayment $invoicePayment): void
    {
        $invoicePayment->refresh();
        $payment = $invoicePayment->payment;

        DB::transaction(function () use ($payment) {
            $this->updateInvoiceService->updateInvoice($payment);
        });
    }
}
