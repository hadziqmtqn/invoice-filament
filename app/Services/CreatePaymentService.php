<?php

namespace App\Services;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Traits\HasMidtransSnap;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreatePaymentService
{
    use HasMidtransSnap;

    /**
     * @throws Throwable
     */
    public static function handle(Invoice $invoice, $amount): ?string
    {
        try {
            $invoice->loadMissing('invoicePaymentPending');

            if (!$invoice->invoicePaymentPending) {
                DB::beginTransaction();
                $payment = new Payment();
                $payment->date = now();
                $payment->amount = $amount;
                $payment->save();
    
                $invoicePayment = new InvoicePayment();
                $invoicePayment->payment_id = $payment->id;
                $invoicePayment->invoice_id = $invoice->id;
                $invoicePayment->amount_applied = $amount;
                $invoicePayment->save();
                DB::commit();
                $params = [
                    'transaction_details' => [
                        'order_id' => $payment->reference_number,
                        'gross_amount' => $payment->amount,
                    ],
                    'customer_details' => [
                        'first_name' => $payment->user?->name,
                        'email' => $payment->user?->email,
                        'phone' => $payment->user?->userProfile?->phone,
                    ],
                    'item_details' => $invoice->invoiceItems->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'name' => $detail->name,
                            'price' => $detail->rate,
                            'quantity' => $detail->qty,
                        ];
                    })->toArray(),
                    'callbacks' => [
                        'finish' => InvoiceResource::getUrl('view', ['record' => $invoice->slug]),
                    ],
                ];

                $snapToken = self::generateMidtransSnapToken($params);
            }else {
                $snapToken = $invoice->invoicePaymentPending?->payment?->midtrans_snap_token;
            }

            return $snapToken;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());
            return null;
        }
    }
}
