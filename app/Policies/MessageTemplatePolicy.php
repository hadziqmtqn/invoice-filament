<?php

namespace App\Policies;

use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_message::template');
    }

    public function view(User $user, MessageTemplate $messageTemplate): bool
    {
        return $user->can('view_message::template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_message::template');
    }

    public function update(User $user, MessageTemplate $messageTemplate): bool
    {
        return $user->can('update_message::template');
    }

    public function delete(User $user, MessageTemplate $messageTemplate): bool
    {
        return $user->can('delete_message::template');
    }
}
