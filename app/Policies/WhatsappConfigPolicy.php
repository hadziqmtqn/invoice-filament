<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappConfig;
use Illuminate\Auth\Access\HandlesAuthorization;

class WhatsappConfigPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_whatsapp::config');
    }

    public function view(User $user, WhatsappConfig $whatsappConfig): bool
    {
        return $user->can('view_whatsapp::config', $whatsappConfig);
    }

    public function create(User $user): bool
    {
        return $user->can('create_whatsapp::config');
    }

    public function update(User $user, WhatsappConfig $whatsappConfig): bool
    {
        return $user->can('update_whatsapp::config', $whatsappConfig);
    }

    public function delete(User $user, WhatsappConfig $whatsappConfig): bool
    {
        return $user->can('delete_whatsapp::config', $whatsappConfig);
    }
}
