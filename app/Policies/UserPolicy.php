<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_main::user');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('view_main::user', $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_main::user');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('update_main::user', $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete_main::user', $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('restore_main::user', $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_main::user');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('force_delete_main::user', $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_main::user');
    }
}
