<?php

namespace App\Policies;

use App\Models\AccessLog;
use App\Models\User;

class AccessLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('catera:access_logs:view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AccessLog $accessLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AccessLog $accessLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AccessLog $accessLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AccessLog $accessLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AccessLog $accessLog): bool
    {
        return false;
    }
}
