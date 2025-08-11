<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_item');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->can('view_item', $item);
    }

    public function create(User $user): bool
    {
        return $user->can('create_item');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('update_item', $item);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_item');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('delete_item', $item);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_item');
    }

    public function restore(User $user, Item $item): bool
    {
        return $user->can('restore_item');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_item');
    }

    public function forceDelete(User $user, Item $item): bool
    {
        return $user->can('force_delete_item');
    }
}
