<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;

use App\Models\Publishing\Publishing;
use App\Models\User\User;

class PublishingPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Publishing $publishing): bool
    {
        if ($user->can('view-releases')) {
            return true;
        }

        if ($user->profile->id === $publishing->profileid) {
            return true;
        }

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
    public function update(User $user, Publishing $publishing): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Publishing $publishing): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Publishing $publishing): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Publishing $publishing): bool
    {
        return false;
    }
}
