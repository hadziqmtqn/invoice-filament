<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRecurringInvoiceJob;
use App\Models\RecurringInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoiceCommand extends Command
{
    protected $signature = 'invoice:generate-recurring';

    protected $description = 'Generate recurring invoices for customers';

    public function handle(): void
    {
        $recurringInvoices = RecurringInvoice::with('lineItems')
            ->where('status', 'active')
            ->where('start_generate_date', '<=', now()->toDateTimeString())
            ->get();

        if ($recurringInvoices->isEmpty()) {
            Log::info('No recurring invoices to process at ' . now());
            $this->info('No recurring invoices to process at ' . now());
            return;
        }

        foreach ($recurringInvoices as $recurringInvoice) {
            $recurringInvoice->start_generate_date = $recurringInvoice->calculateNextInvoiceDate();
            $recurringInvoice->last_generated_date = now();
            $recurringInvoice->save();

            Log::info('Processing Recurring Invoice ID: ' . $recurringInvoice->code . ' at ' . $recurringInvoice->start_generate_date);
            $this->info("Dispatched job for Recurring Invoice ID: " . $recurringInvoice->code);
            // Dispatch the job to generate the recurring invoice
            GenerateRecurringInvoiceJob::dispatch($recurringInvoice);
        }
    }
}
