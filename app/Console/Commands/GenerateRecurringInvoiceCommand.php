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
            ->get();

        $count = 0;

        foreach ($recurringInvoices as $recurringInvoice) {
            Log::info('Processing Recurring Invoice ID: ' . $recurringInvoice->code . ' at ' . $recurringInvoice->start_generate_date);
            // Hitung next_invoice_date berdasarkan last_generated_date
            // Pastikan recurringInvoice->next_invoice_date menggunakan last_generated_date sebagai acuan
            // Jika belum pernah generate, gunakan start_date atau date
            $nextDate = $recurringInvoice->next_invoice_date;

            // Selama next_invoice_date sudah waktunya, buat invoice (menutup kasus server/scheduler down)
            while ($recurringInvoice->start_generate_date <= now()) {
                // Dispatch job dengan data snapshot recurring + tanggal invoice yang ingin dibuat
                GenerateRecurringInvoiceJob::dispatch($recurringInvoice);

                $this->info("Dispatched job for Recurring Invoice ID: " . $recurringInvoice->code . " at $nextDate");

                // Hitung next occurrence berikutnya
                $recurringInvoice->last_generated_date = $nextDate;
                $recurringInvoice->save();

                // (refresh property agar custom attribute next_invoice_date menghitung berdasarkan last_generated_date baru)
                $recurringInvoice->refresh();
                $recurringInvoice->start_generate_date = $recurringInvoice->calculateNextInvoiceDate();
                $recurringInvoice->save();
                $nextDate = $recurringInvoice->next_invoice_date;
                $count++;
            }

            $this->info("Dispatched job for Recurring Invoice ID: " . $recurringInvoice->code);
        }

        if ($count === 0) {
            Log::info('No recurring invoices to generate at this time.');
            $this->info('No recurring invoices to generate at this time.');
        }
    }
}
