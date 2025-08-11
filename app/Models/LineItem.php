<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineItem extends Model
{
    protected $fillable = [
        'recurring_invoice_id',
        'item_id',
        'name',
        'qty',
        'unit',
        'rate',
        'description',
    ];
}
