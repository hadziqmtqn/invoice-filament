<?php

namespace App\Services;

use App\Models\Item;

class ItemService
{
    /**
     * @param array $itemAvailable
     * @return mixed
     */
    public static function dropdownOptions(array $itemAvailable = []): mixed
    {
        return Item::when($itemAvailable, function ($query) use ($itemAvailable) {
            $query->whereIn('id', $itemAvailable);
        })
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn(Item $item) => [$item->id => $item->name])
            ->toArray();
    }
}
