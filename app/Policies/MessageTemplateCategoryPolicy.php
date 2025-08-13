<?php

namespace App\Policies;

use App\Models\MessageTemplateCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageTemplateCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_message::template::category');
    }

    public function view(User $user, MessageTemplateCategory $messageTemplateCategory): bool
    {
        return $user->can('view_message::template::category', $messageTemplateCategory);
    }

    public function create(User $user): bool
    {
        return $user->can('create_message::template::category');
    }

    public function update(User $user, MessageTemplateCategory $messageTemplateCategory): bool
    {
        return $user->can('update_message::template::category', $messageTemplateCategory);
    }

    public function delete(User $user, MessageTemplateCategory $messageTemplateCategory): bool
    {
        return $user->can('delete_message::template::category', $messageTemplateCategory);
    }
}
