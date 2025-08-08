<?php

namespace App\Policies;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_bank');
    }

    public function view(User $user, Bank $bank): bool
    {
        return $user->can('view_bank');
    }

    public function create(User $user): bool
    {
        return $user->can('create_bank');
    }

    public function update(User $user, Bank $bank): bool
    {
        return $user->can('update_bank');
    }

    public function delete(User $user, Bank $bank): bool
    {
        return $user->can('delete_bank');
    }
}
