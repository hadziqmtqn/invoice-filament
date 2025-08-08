<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_invoice');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('view_invoice') || $user->id === $invoice->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_invoice');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('update_invoice') || $user->id === $invoice->user_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('delete_invoice') || $user->id === $invoice->user_id;
    }
}
