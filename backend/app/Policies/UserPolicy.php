<?php

namespace App\Policies;

use App\Enums\Capability;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return Capability::UsersView->isGrantedTo($user->role);
    }

    public function view(User $user, User $target): bool
    {
        return Capability::UsersView->isGrantedTo($user->role);
    }

    public function create(User $user): bool
    {
        return Capability::UsersManage->isGrantedTo($user->role);
    }

    public function update(User $user, User $target): bool
    {
        return Capability::UsersManage->isGrantedTo($user->role);
    }

    public function changeRole(User $user, User $target): bool
    {
        return Capability::UsersManage->isGrantedTo($user->role)
            && ! $user->is($target);
    }

    public function changeStatus(User $user, User $target): bool
    {
        return Capability::UsersManage->isGrantedTo($user->role);
    }
}
