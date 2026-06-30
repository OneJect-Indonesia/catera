<?php

namespace App\Policies;

use App\Models\Authorized;
use App\Models\User;

class AuthorizedPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('catera:authorized:view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Authorized $authorized): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('catera:authorized:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Authorized $authorized): bool
    {
        return $user->hasPermissionTo('catera:authorized:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Authorized $authorized): bool
    {
        return $user->hasPermissionTo('catera:authorized:delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Authorized $authorized): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Authorized $authorized): bool
    {
        return false;
    }
}
