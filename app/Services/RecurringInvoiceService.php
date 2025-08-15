<?php

namespace App\Services;

use App\Models\RecurringInvoice;

class RecurringInvoiceService
{
    public static function selectOptions($userId, $selfId = null): array
    {
        return RecurringInvoice::userId($userId)
            ->when($selfId, function ($query) use ($selfId) {
                return $query->where('id', $selfId);
            })
            ->where('status', 'active')
            ->pluck('title', 'id')
            ->toArray();
    }
}
