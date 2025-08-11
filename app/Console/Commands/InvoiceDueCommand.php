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
        $today = now()->toDateString();

        // Ambil semua invoice yang due_date hari ini
        $invoices = Invoice::whereDate('due_date', '<=', $today)
            ->whereNotIn('status', ['paid', 'draft'])
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        foreach ($invoices as $invoice) {
            // Lakukan sesuatu dengan invoice yang jatuh tempo
            $invoice->status = 'overdue';
            $invoice->save();
            // Contoh: Tampilkan info dan log
            $this->info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " jatuh tempo hari ini.");
            Log::info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " jatuh tempo hari ini.");
        }
    }
}
