<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('view_user', $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_user');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('update_user', $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete_user', $model) && $model->invoices->isEmpty() && $model->payments->isEmpty() && $model->recurringInvoices->isEmpty();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('restore_user', $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_user');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('force_delete_user', $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_user');
    }
}
