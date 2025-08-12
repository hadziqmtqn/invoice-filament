<?php

namespace App\Observers;

use App\Models\RecurringInvoice;
use Illuminate\Support\Str;

class RecurringInvoiceObserver
{
    public function creating(RecurringInvoice $recurringInvoice): void
    {
        $recurringInvoice->slug = Str::uuid()->toString();
        $recurringInvoice->invoice_number = time();
        $recurringInvoice->serial_number = RecurringInvoice::max('serial_number') + 1;
        $recurringInvoice->code = 'RINV' . Str::upper(Str::random(6)) . '-' . $recurringInvoice->serial_number;
        $recurringInvoice->start_generate_date = $recurringInvoice->calculateNextInvoiceDate();
    }

    public function updating(RecurringInvoice $recurringInvoice): void
    {
        $recurringInvoice->start_generate_date = $recurringInvoice->getFirstNextInvoiceDate();
    }
}
