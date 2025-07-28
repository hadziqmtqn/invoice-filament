<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'phone',
        'province',
        'city',
        'district',
        'village',
        'street',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
