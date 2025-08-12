<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRecurringInvoiceJob;
use App\Models\RecurringInvoice;
use Illuminate\Console\Command;

class GenerateRecurringInvoiceCommand extends Command
{
    protected $signature = 'invoice:generate-recurring';

    protected $description = 'Generate recurring invoices for customers';

    public function handle(): void
    {
        $recurringInvoices = RecurringInvoice::with('lineItems')
            ->where('status', 'active')
            ->get()
            ->filter(function ($recurringInvoice) {
                return $recurringInvoice->next_invoice_date <= now();
            });

        foreach ($recurringInvoices as $recurringInvoice) {
            GenerateRecurringInvoiceJob::dispatch($recurringInvoice);

            $this->info("Dispatched job for Recurring Invoice ID: " . $recurringInvoice->code);
        }
    }
}
