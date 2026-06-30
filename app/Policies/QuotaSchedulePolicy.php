<?php

namespace App\Policies;

use App\Models\QuotaSchedule;
use App\Models\User;

class QuotaSchedulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('catera:quota_scheduling:view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, QuotaSchedule $quotaSchedule): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('catera:quota_scheduling:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QuotaSchedule $quotaSchedule): bool
    {
        return $user->hasPermissionTo('catera:quota_scheduling:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QuotaSchedule $quotaSchedule): bool
    {
        return $user->hasPermissionTo('catera:quota_scheduling:delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QuotaSchedule $quotaSchedule): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QuotaSchedule $quotaSchedule): bool
    {
        return false;
    }
}
