<?php

namespace App\Policies;

use App\Enums\Capability;
use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function viewAny(User $user): bool
    {
        return Capability::CatalogView->isGrantedTo($user->role);
    }

    public function view(User $user, Book $book): bool
    {
        return Capability::CatalogView->isGrantedTo($user->role);
    }

    public function create(User $user): bool
    {
        return Capability::CatalogManage->isGrantedTo($user->role);
    }

    public function update(User $user, Book $book): bool
    {
        return Capability::CatalogManage->isGrantedTo($user->role);
    }

    public function delete(User $user, Book $book): bool
    {
        return Capability::CatalogManage->isGrantedTo($user->role);
    }
}
