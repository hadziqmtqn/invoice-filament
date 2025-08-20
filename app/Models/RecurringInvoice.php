<?php

namespace App\Models;

use App\Helpers\NextDate;
use App\Observers\RecurringInvoiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([RecurringInvoiceObserver::class])]
class RecurringInvoice extends Model
{
    protected $fillable = [
        'slug',
        'invoice_number',
        'serial_number',
        'code',
        'user_id',
        'title',
        'date',
        'due_date',
        'recurrence_frequency',
        'repeat_every',
        'discount',
        'note',
        'status',
        'start_generate_date',
        'last_generated_date'
    ];

    protected function casts(): array
    {
        return [
            'slug' => 'string',
            'date' => 'datetime',
            'due_date' => 'datetime',
            'start_generate_date' => 'datetime',
            'last_generated_date' => 'datetime'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class, 'recurring_invoice_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_invoice_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // TODO Attribute
    protected function totalPrice(): Attribute
    {
        $totalPrice = $this->lineItems->sum('rate');
        $discountAmount = ($totalPrice * $this->discount) / 100;
        $totalPrice -= $discountAmount;

        return Attribute::make(
            get: fn() => $totalPrice,
        );
    }

    protected function totalPriceBeforeDiscount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->lineItems->sum('rate'),
        );
    }

    protected function nextInvoiceDate(): Attribute
    {
        return Attribute::make(
            get: fn() => NextDate::calculateNextDate($this->date, $this->recurrence_frequency, $this->repeat_every)
        );
    }

    // TODO Scope
    #[Scope]
    protected function userId(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
