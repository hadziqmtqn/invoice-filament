<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDueNotification extends Model
{
    protected $fillable = [
        'invoice_id',
        'user_id',
        'notification_date',
        'days_before_due',
    ];

    protected function casts(): array
    {
        return [
            'notification_date' => 'date',
        ];
    }
}
