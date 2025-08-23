<?php

namespace App\Console\Commands;

use App\Jobs\InvoiceWillDueMessageJob;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InvoiceWillDueCommand extends Command
{
    protected $signature = 'invoice:will-due';

    protected $description = 'Command to notify users about invoices that will be due within the next 7 days or 3 days';

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
            $user = $invoice->user;
            $whatsappNumber = $user?->userProfile?->phone;
            if (!$whatsappNumber) continue;

            $daysLeft = now()->diffInDays($invoice->due_date);

            // Kirim hanya di hari ke-7 dan ke-3 sebelum jatuh tempo
            if (in_array($daysLeft, [7, 3])) {
                // Dispatch job
                InvoiceWillDueMessageJob::dispatch($invoice);

                // Log informasi invoice yang jatuh tempo
                $this->info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " akan jatuh tempo dalam " . $daysLeft . " hari pada " . $invoice->due_date->format('Y-m-d'));
                Log::info("Invoice #" . $invoice->code . " untuk " . $invoice->user?->name . " akan jatuh tempo dalam " . $daysLeft . " hari pada " . $invoice->due_date->format('Y-m-d'));
            }
        }
    }
}
