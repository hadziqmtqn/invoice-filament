<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Payment;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PaymentCallbackService
{
    use ApiResponse;

    public function handle(Request $request, $provider): JsonResponse
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
            $payment = Payment::where('reference_number', $orderId)
                ->first();

            if (!$payment) {
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
                default => $payment->status, // fallback ke status sebelumnya jika tidak dikenali
            };

            $this->updateInvoice($payment, [
                'payment_source' => 'payment_gateway',
                'status' => $status,
                'payment_method' => $paymentType,
                'payment_channel' => $paymentChannel,
                'transaction_time' => $request->input('transaction_time'),
                'expiry_time' => $request->input('expiry_time'),
                'settlement_time' => $request->input('settlement_time')
            ]);

            return $this->apiResponse('Success', null, null, Response::HTTP_OK);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->apiResponse('Internal server error', null, null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Determine payment channel based on payment type and request data
     */
    private function determinePaymentChannel(Request $request, string $paymentType): ?string
    {
        $paymentChannel = null;

        // Virtual Account (Bank Transfer)
        if ($request->has('va_numbers') && is_array($request->va_numbers) && count($request->va_numbers) > 0) {
            $paymentChannel = $request->va_numbers[0]['bank'] ?? null;
            Log::info('Virtual Account Payment Channel detected', [
                'payment_type' => $paymentType,
                'bank' => $paymentChannel
            ]);
        }
        // Permata Virtual Account
        elseif ($request->filled('permata_va_number')) {
            $paymentChannel = 'permata';
            Log::info('Permata VA Payment Channel detected', [
                'payment_type' => $paymentType,
                'permata_va_number' => $request->input('permata_va_number')
            ]);
        }
        // Store Payment (Alfamart/Indomaret)
        elseif ($request->filled('store')) {
            $paymentChannel = $request->input('store');
            Log::info('Store Payment Channel detected', [
                'payment_type' => $paymentType,
                'store' => $paymentChannel
            ]);
        }
        // QRIS Payment
        elseif ($paymentType === 'qris' && $request->filled('issuer')) {
            $paymentChannel = $request->input('issuer'); // Dana, GoPay, LinkAja, dll
            Log::info('QRIS Payment Channel detected', [
                'payment_type' => $paymentType,
                'issuer' => $paymentChannel
            ]);
        }
        // Credit Card
        elseif ($paymentType === 'credit_card' && $request->filled('bank')) {
            $paymentChannel = $request->input('bank');
            Log::info('Credit Card Payment Channel detected', [
                'payment_type' => $paymentType,
                'bank' => $paymentChannel
            ]);
        }
        // E-Wallet (ShopeePay, GoPay Direct)
        elseif (in_array($paymentType, ['shopeepay', 'gopay']) && $request->filled('channel_response_code')) {
            $paymentChannel = $paymentType;
            Log::info('E-Wallet Payment Channel detected', [
                'payment_type' => $paymentType,
                'channel' => $paymentChannel
            ]);
        }
        // Bank Transfer (Mandiri ClickPay, CIMB Clicks, BCA KlikBCA, dll)
        elseif (in_array($paymentType, ['mandiri_clickpay', 'cimb_clicks', 'bca_klikbca', 'bca_klikpay', 'bri_epay', 'echannel', 'danamon_online'])) {
            $paymentChannel = $paymentType;
            Log::info('Bank Transfer Payment Channel detected', [
                'payment_type' => $paymentType,
                'channel' => $paymentChannel
            ]);
        }

        // Fallback jika tidak ada channel yang terdeteksi
        if (!$paymentChannel) {
            $paymentChannel = $paymentType;
            Log::info('Payment Channel fallback to payment type', [
                'payment_type' => $paymentType,
                'fallback_channel' => $paymentChannel
            ]);
        }

        return $paymentChannel;
    }

    /**
     * @throws Throwable
     */
    public function updateInvoice(Payment $payment, array $data): void
    {
        DB::transaction(function () use ($payment, $data) {
            // TODO Update transaction
            $payment->payment_source = $data['payment_source'];
            $payment->status = $payment->status != 'PAID' ? $data['status'] : $payment->status;
            $payment->payment_method = array_key_exists('payment_method', $data) ? $data['payment_method'] : null;
            $payment->payment_channel = array_key_exists('payment_channel', $data) ? $data['payment_channel'] : null;
            $payment->transaction_time = $data['transaction_time'];

            $payment->save();

            // TODO Transaksi pembayaran sekolah
            if ($payment->invoicePayments->isNotEmpty() && ($data['status'] == 'PAID')) {
                foreach ($payment->invoicePayments as $invoicePayment) {
                    $invoice = $invoicePayment->invoice;
                    $invoice->status = $payment->total_bill == $invoice->total_price ? InvoiceStatus::PAID->value : InvoiceStatus::PARTIALLY_PAID->value;
                    $invoice->save();
                }
            }

            // Log successful invoice update
            Log::info('Invoice updated successfully', [
                'payment_reference_number' => $payment->reference_number,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'payment_channel' => $payment->payment_channel
            ]);
        });
    }
}
