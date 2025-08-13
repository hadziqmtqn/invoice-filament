<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;

class ScheduleTaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_monitored::scheduled::task');
    }

    public function view(User $user, MonitoredScheduledTask $monitoredScheduledTask): bool
    {
        return $user->can('view_monitored::scheduled::task', $monitoredScheduledTask);
    }

    public function create(User $user): bool
    {
        return $user->can('create_monitored::scheduled::task');
    }

    public function update(User $user, MonitoredScheduledTask $monitoredScheduledTask): bool
    {
        return $user->can('update_monitored::scheduled::task', $monitoredScheduledTask);
    }

    public function delete(User $user, MonitoredScheduledTask $monitoredScheduledTask): bool
    {
        return $user->can('delete_monitored::scheduled::task', $monitoredScheduledTask);
    }

    public function restore(User $user, MonitoredScheduledTask $monitoredScheduledTask): bool
    {
        return $user->can('restore_monitored::scheduled::task', $monitoredScheduledTask);
    }

    public function forceDelete(User $user, MonitoredScheduledTask $monitoredScheduledTask): bool
    {
        return $user->can('force_delete_monitored::scheduled::task', $monitoredScheduledTask);
    }
}
