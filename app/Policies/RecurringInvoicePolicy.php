<?php

namespace App\Policies;

use App\Models\RecurringInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecurringInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_recurring::invoice');
    }

    public function view(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->can('view_recurring::invoice');
    }

    public function create(User $user): bool
    {
        return $user->can('create_recurring::invoice');
    }

    public function update(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->can('update_recurring::invoice');
    }

    public function delete(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->can('delete_recurring::invoice');
    }
}
