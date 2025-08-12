<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InvoiceDueCommand extends Command
{
    protected $signature = 'invoice:due';

    protected $description = 'Command description';

    public function handle(): void
    {
        // Ambil semua invoice yang due_date hari ini
        $invoices = Invoice::with('user.userProfile:id,user_id,phone')
            ->whereDate('due_date', '<=', now()->toDateString())
            ->whereNotIn('status', ['paid', 'draft', 'overdue'])
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        foreach ($invoices as $invoice) {
            // Lakukan sesuatu dengan invoice yang jatuh tempo
            $invoice->status = 'overdue';
            $invoice->save();

            // Log informasi invoice yang jatuh tempo
            $this->info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " jatuh tempo hari ini.");
            Log::info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " jatuh tempo hari ini.");
        }
    }
}
