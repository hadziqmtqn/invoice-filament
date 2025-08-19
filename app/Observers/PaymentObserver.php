<?php

namespace App\Observers;

use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentObserver
{
    public function creating(Payment $payment): void
    {
        $payment->slug = Str::uuid()->toString();
        $payment->serial_number = Payment::max('serial_number') + 1;
        $payment->reference_number = strtoupper('REF' . Str::random(6) . Str::padLeft($payment->serial_number, 6, '0'));
        $payment->payment_source = $payment->payment_source == 'payment_gateway' ? 'payment_gateway' : 'cash';
    }

    public function updating(Payment $payment): void
    {
    }
}
