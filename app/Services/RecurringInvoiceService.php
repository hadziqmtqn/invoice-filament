<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\RecurringInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecurringInvoiceService
{
    public static function selectOptions($userId, $selfId = null): array
    {
        return RecurringInvoice::userId($userId)
            ->when($selfId, function ($query) use ($selfId) {
                return $query->where('id', $selfId);
            })
            ->where('status', 'active')
            ->pluck('title', 'id')
            ->toArray();
    }

    /**
     * @throws Throwable
     */
    public static function generateFromInvoice(Invoice $invoice, array $data): void
    {
        try {
            $invoice->loadMissing('invoiceItems');

            DB::beginTransaction();
            $recurringInvoice = new RecurringInvoice();
            $recurringInvoice->user_id = $invoice->user_id;
            $recurringInvoice->title = $data['title'];
            $recurringInvoice->date = $data['date'];
            $recurringInvoice->recurrence_frequency = $data['recurrence_frequency'];
            $recurringInvoice->repeat_every = $data['repeat_every'];
            $recurringInvoice->status = $data['status'];
            $recurringInvoice->save();

            foreach ($invoice->invoiceItems as $invoiceItem) {
                $lineItem = new LineItem();
                $lineItem->recurring_invoice_id = $recurringInvoice->id;
                $lineItem->item_id = $invoiceItem->item_id;
                $lineItem->name = $invoiceItem->name;
                $lineItem->qty = $invoiceItem->qty;
                $lineItem->unit = $invoiceItem->unit;
                $lineItem->rate = $invoiceItem->rate;
                $lineItem->description = $invoiceItem->description;
                $lineItem->save();
            }

            $invoice->recurring_invoice_id = $recurringInvoice->id;
            $invoice->save();
            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::error($throwable->getMessage());
            throw $throwable;
        }
    }
}
