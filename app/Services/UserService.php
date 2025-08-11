<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * @return array
     */
    public static function dropdownOptions($selfId = null): array
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'user'))
            ->when($selfId, fn($query) => $query->where('id', $selfId))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn(User $user) => [$user->id => $user->name])
            ->toArray();
    }
}
