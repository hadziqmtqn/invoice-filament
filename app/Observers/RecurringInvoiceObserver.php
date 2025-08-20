<?php

namespace App\Observers;

use App\Helpers\NextDate;
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
        $recurringInvoice->start_generate_date = NextDate::calculateNextDate($recurringInvoice->date, $recurringInvoice->recurrence_frequency, $recurringInvoice->repeat_every);
    }

    public function updating(RecurringInvoice $recurringInvoice): void
    {
        $recurringInvoice->start_generate_date = NextDate::calculateNextDate($recurringInvoice->date, $recurringInvoice->recurrence_frequency, $recurringInvoice->repeat_every);
    }
}
