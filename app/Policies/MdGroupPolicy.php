<?php

namespace App\Policies;

use App\Models\MdGroup;
use App\Models\User;

class MdGroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('catera:md_group:view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MdGroup $mdGroup): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('catera:md_group:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MdGroup $mdGroup): bool
    {
        return $user->hasPermissionTo('catera:md_group:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MdGroup $mdGroup): bool
    {
        return $user->hasPermissionTo('catera:md_group:delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MdGroup $mdGroup): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MdGroup $mdGroup): bool
    {
        return false;
    }
}
