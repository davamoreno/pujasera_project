<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;
use App\Models\Staff;

use Illuminate\Auth\Access\Response;

class MenuItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function before(Staff $staff,string $ability):bool|null
    {
        if($staff->role->nama==='Admin'){
            return true;
        }
        return null;
    }
    public function viewAny(Staff $staff): bool
    {
        return $staff->role->nama === 'Pemilik Tenant';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Staff $staff, MenuItem $menuItem): bool
    {
        return $staff->tenant->id === $menuItem->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Staff $staff): bool
    {
        return $staff->role->nama === 'Pemilik Tenant';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Staff $staff, MenuItem $menuItem): bool
    {
        return $staff->tenant->id === $menuItem->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Staff $staff, MenuItem $menuItem): bool
    {
        return $staff->tenant->id === $menuItem->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MenuItem $menuItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MenuItem $menuItem): bool
    {
        return false;
    }
}
