<?php

namespace App\Services;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PaymentCallbackService
{
    use ApiResponse;

    public function handle(Request $request, $provider)
    {
        try {
            if ($provider === 'midtrans') {
                $serverKey = config('midtrans.server_key');
                $signatureKey = $request->input('signature_key');
                $orderId = $request->input('order_id');
                $statusCode = $request->input('status_code');
                $grossAmount = $request->input('gross_amount');

                $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
                if ($mySignature !== $signatureKey) {
                    Log::warning('Midtrans Signature Key not valid', ['data' => $request->all()]);
                    return $this->apiResponse('Invalid Signature', null, null, Response::HTTP_FORBIDDEN);
                }

                Log::info('*Midtrans Response:* ' . json_encode($request->all()));
            } else {
                return $this->apiResponse('Unsupported provider', null, null, Response::HTTP_BAD_REQUEST);
            }

            // Temukan invoice berdasarkan order_id (biasanya code invoice)
            $transaction = Transaction::with([
                'schoolPayments.billDetail',
                'schoolPayments.billsOncePay.billPrice',
                'schoolPayments.billsOncePay.schoolPayments' => fn($query) => $query->whereHas('transaction', fn($query) => $query->filterByStatus('PAID')),
                'savingTransactions'
            ])
                ->where('code', $orderId)->first();

            if (!$transaction) {
                Log::warning('Transaction not found', ['order_id' => $orderId]);
                return $this->apiResponse('Transaction Not Found');
            }

            // Update status invoice
            $transactionStatus = $request->input('transaction_status');
            $paymentType = $request->input('payment_type');
            $paymentChannel = $this->determinePaymentChannel($request, $paymentType);

            // Penentuan status
            $status = match ($transactionStatus) {
                'capture', 'settlement' => 'PAID',
                'pending' => 'PENDING',
                'cancel', 'expire', 'deny' => strtoupper($transactionStatus),
                default => $transaction->status, // fallback ke status sebelumnya jika tidak dikenali
            };

            $this->updateInvoice($transaction, [
                'payment_source' => 'payment_gateway',
                'status' => $status,
                'payment_method' => $paymentType,
                'payment_channel' => $paymentChannel,
                'transaction_time' => $request->input('transaction_time'),
                'expiry_time' => $request->input('expiry_time'),
                'settlement_time' => $request->input('settlement_time')
            ]);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->apiResponse('Internal server error', null, null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
