<?php

namespace App\Observers;

use App\Enums\DataStatus;
use App\Models\Invoice;
use App\Services\UpdateInvoiceService;

class InvoiceObserver
{
    protected UpdateInvoiceService $updateInvoiceService;

    /**
     * @param UpdateInvoiceService $updateInvoiceService
     */
    public function __construct(UpdateInvoiceService $updateInvoiceService)
    {
        $this->updateInvoiceService = $updateInvoiceService;
    }

    public function saved(Invoice $invoice): void
    {
        $invoice->refresh();
        if (!in_array($invoice->status, [DataStatus::DRAFT->value, DataStatus::SENT->value])) {
            $this->updateInvoiceService->updateStatusInvoice($invoice);
        }
    }
}