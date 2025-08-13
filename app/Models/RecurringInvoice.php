<?php

namespace App\Models;

use App\Observers\RecurringInvoiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // 1. First Next Invoice Date (saat recurring baru)
    /*public function getFirstNextInvoiceDate(): Carbon
    {
        $baseDate = Carbon::parse($this->date);
        $repeatEvery = (int)($this->repeat_every ?: 1);

        return match ($this->recurrence_frequency) {
            'seconds' => $baseDate->copy()->addSeconds($repeatEvery),
            'minutes' => $baseDate->copy()->addMinutes($repeatEvery),
            'days' => $baseDate->copy()->addDays($repeatEvery),
            'weeks' => $baseDate->copy()->addWeeks($repeatEvery),
            'months' => $baseDate->copy()->addMonths($repeatEvery),
            'years' => $baseDate->copy()->addYears($repeatEvery),
            default => $baseDate,
        };
    }*/

    // 2. Next Invoice Date (berjalan)
    public function calculateNextInvoiceDate(): Carbon
    {
        $date = $this->date->copy(); // Carbon instance, JANGAN pakai reference!
        $now = now();

        $repeatEvery = (int)($this->repeat_every ?: 1);

        // Cek jika next interval sudah lewat, tambahkan terus sampai lewat now
        while ($date <= $now) {
            $date = match ($this->recurrence_frequency) {
                'seconds' => $date->addSeconds($repeatEvery),
                'minutes' => $date->addMinutes($repeatEvery),
                'days' => $date->addDays($repeatEvery),
                'weeks' => $date->addWeeks($repeatEvery),
                'months' => $date->addMonths($repeatEvery),
                'years' => $date->addYears($repeatEvery),
                default => $date,
            };
        }

        return $date;
    }

    // TODO Attribute
    protected function totalPrice(): Attribute
    {
        $totalPrice = $this->lineItems->sum(function ($item) {
            return $item->qty * $item->rate;
        });
        $discountAmount = ($totalPrice * $this->discount) / 100;
        $totalPrice -= $discountAmount;

        return Attribute::make(
            get: fn() => $totalPrice,
        );
    }

    protected function nextInvoiceDate(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->calculateNextInvoiceDate()
        );
    }
}
