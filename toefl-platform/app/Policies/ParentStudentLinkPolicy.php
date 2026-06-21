<?php

namespace App\Policies;

use App\Models\ParentStudentLink;
use App\Models\User;

class ParentStudentLinkPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua user yang terautentikasi bisa melihat daftar link mereka
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ParentStudentLink $link): bool
    {
        // User bisa melihat jika mereka adalah parent atau student di link tersebut
        return $user->id === $link->parent_id 
            || $user->id === $link->student_id
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Student bisa generate invite code
        // Parent bisa input invite code
        return $user->isStudent() || $user->isParent();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ParentStudentLink $link): bool
    {
        // Student bisa approve/revoke
        // Admin juga bisa
        if ($user->id === $link->student_id) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve the link.
     */
    public function approve(User $user, ParentStudentLink $link): bool
    {
        // Hanya student yang bisa approve (atau admin)
        if ($user->id === $link->student_id) {
            return $link->isPending();
        }

        if ($user->isAdmin()) {
            return $link->isPending();
        }

        return false;
    }

    /**
     * Determine whether the user can revoke the link.
     */
    public function revoke(User $user, ParentStudentLink $link): bool
    {
        // Hanya student yang bisa revoke (atau admin)
        if ($user->id === $link->student_id) {
            return $link->isActive();
        }

        if ($user->isAdmin()) {
            return $link->isActive();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ParentStudentLink $link): bool
    {
        // Soft delete hanya untuk admin
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ParentStudentLink $link): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ParentStudentLink $link): bool
    {
        return $user->isSuperAdmin();
    }
}
