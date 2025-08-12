<?php

namespace App\Console\Commands;

use App\Jobs\InvoiceWillDueMessageJob;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InvoiceWillDueCommand extends Command
{
    protected $signature = 'invoice:will-due';

    protected $description = 'Command description';

    public function handle(): void
    {
        /**
         * Ambil semua invoice yang due_date kurang dari/sama 7 hari dari sekarang
         * dan due_date kurang dari/sama 3 hari dari sekarang
         * dan status invoice bukan paid, draft, atau overdue.
         */
        $invoices = Invoice::with('user.userProfile:id,user_id,phone')
            ->where(function ($query) {
                $query->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                      ->orWhereDate('due_date', '<=', now()->addDays(3)->toDateString());
            })
            ->whereNotIn('status', ['paid', 'draft', 'overdue'])
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        foreach ($invoices as $invoice) {
            // Dispatch job untuk mengirim pesan
            InvoiceWillDueMessageJob::dispatch([
                'user_name' => $invoice->user?->name,
                'invoice_name' => $invoice->name,
                'amount' => $invoice->amount,
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'whatsapp_number' => $invoice->user?->whatsapp_number,
            ]);

            // Log informasi invoice yang jatuh tempo
            $this->info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " akan jatuh tempo pada " . $invoice->due_date->format('Y-m-d'));
            Log::info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " akan jatuh tempo pada " . $invoice->due_date->format('Y-m-d'));
        }
    }
}
