<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateRecurringInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RecurringInvoice $recurringInvoice;

    /**
     * @param RecurringInvoice $recurringInvoice
     */
    public function __construct(RecurringInvoice $recurringInvoice)
    {
        $this->recurringInvoice = $recurringInvoice;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();
            $invoice = new Invoice();
            $invoice->fill([
                'recurring_invoice_id' => $this->recurringInvoice->id,
                'user_id' => $this->recurringInvoice->user_id,
                'title' => $this->recurringInvoice->title,
                'date' => now(),
                'due_date' => now()->addDays(14),
                'discount' => $this->recurringInvoice->discount,
                'note' => $this->recurringInvoice->note,
            ]);
            $invoice->save();

            $lineItems = $this->recurringInvoice->lineItems;
            foreach ($lineItems as $lineItem) {
                $invoice->invoiceItems()->create([
                    'item_id' => $lineItem->item_id,
                    'name' => $lineItem->name,
                    'qty' => $lineItem->qty,
                    'rate' => $lineItem->rate,
                    'description' => $lineItem->description,
                ]);
            }

            Log::info('Recurring invoice generated successfully', [
                'recurring_invoice_code' => $this->recurringInvoice->code,
                'start_generate_date' => $this->recurringInvoice->start_generate_date->toDateTimeString(),
                'next_invoice_date' => $this->recurringInvoice->next_invoice_date->toDateTimeString(),
                'last_generated_date' => $this->recurringInvoice->last_generated_date ? $this->recurringInvoice->last_generated_date->toDateTimeString() : null,
            ]);

            $invoice->refresh();
            $recurringInvoice = $this->recurringInvoice;
            $recurringInvoice->last_generated_date = now();
            $recurringInvoice->save();
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());
        }
    }
}
