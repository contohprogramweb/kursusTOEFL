<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPreference;

class UserPreferencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserPreference $userPreference): bool
    {
        // User dapat melihat preferensi mereka sendiri atau jika admin
        return $user->id === $userPreference->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Setiap user bisa membuat preferensi mereka sendiri
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserPreference $userPreference): bool
    {
        // User hanya bisa mengupdate preferensi mereka sendiri atau jika admin/super_admin
        return $user->id === $userPreference->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserPreference $userPreference): bool
    {
        // Hanya admin/super_admin yang bisa menghapus preferensi
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserPreference $userPreference): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserPreference $userPreference): bool
    {
        return $user->isSuperAdmin();
    }
}
