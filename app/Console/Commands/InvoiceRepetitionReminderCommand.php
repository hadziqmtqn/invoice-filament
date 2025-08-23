<?php

namespace App\Console\Commands;

use App\Enums\RecurringInvoiceStatus;
use App\Jobs\NewRepetitionInvoiceMessageJob;
use App\Models\RecurringInvoice;
use Illuminate\Console\Command;

class InvoiceRepetitionReminderCommand extends Command
{
    protected $signature = 'invoice:repetition-reminder';

    protected $description = 'Pengingat akan ada faktur baru sebelum dibuat sejak 1 hari';

    public function handle(): void
    {
        $recurringInvoices = RecurringInvoice::with('user.userProfile')
            ->whereDate('start_generate_date', now()->addDay()->toDateString())
            ->filterByStatus(RecurringInvoiceStatus::ACTIVE->value)
            ->get();

        foreach ($recurringInvoices as $recurringInvoice) {
            NewRepetitionInvoiceMessageJob::dispatch($recurringInvoice);
        }
    }
}
