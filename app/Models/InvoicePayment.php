<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount_applied',
    ];
}
