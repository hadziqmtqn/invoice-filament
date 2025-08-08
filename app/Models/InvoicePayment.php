<?php

namespace App\Models;

use App\Observers\InvoicePaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([InvoicePaymentObserver::class])]
class InvoicePayment extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount_applied',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
