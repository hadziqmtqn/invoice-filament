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
        return $user->can('view_any_message::template_category');
    }

    public function view(User $user, MessageTemplateCategory $messageTemplateCategory): bool
    {
        return $user->can('view_message::template_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_message::template_category');
    }

    public function update(User $user, MessageTemplateCategory $messageTemplateCategory): bool
    {
        return $user->can('update_message::template_category');
    }

    public function delete(User $user, MessageTemplateCategory $messageTemplateCategory): bool
    {
        return $user->can('delete_message::template_category');
    }
}
