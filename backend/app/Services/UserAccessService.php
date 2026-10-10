<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Exceptions\LastActiveAdministrator;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserAccessService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function changeRole(User $target, UserRole $newRole, User $actor): User
    {
        return DB::transaction(function () use ($target, $newRole, $actor): User {
            $activeAdministratorIds = $this->lockActiveAdministrators();
            $user = User::query()->lockForUpdate()->findOrFail($target->getKey());

            if ($user->role === $newRole) {
                return $user;
            }

            if (
                $user->role === UserRole::Admin
                && $user->is_active
                && $newRole !== UserRole::Admin
                && $activeAdministratorIds->count() === 1
            ) {
                throw new LastActiveAdministrator;
            }

            $oldRole = $user->role;
            $user->role = $newRole;
            $user->updated_by = $actor->getKey();
            $user->save();

            $this->auditService->record(
                AuditEvent::UserRoleChanged,
                $actor,
                $user,
                [
                    'old_role' => $oldRole->value,
                    'new_role' => $newRole->value,
                ],
            );

            return $user;
        });
    }

    public function changeActiveStatus(User $target, bool $isActive, User $actor): User
    {
        return DB::transaction(function () use ($target, $isActive, $actor): User {
            $activeAdministratorIds = $this->lockActiveAdministrators();
            $user = User::query()->lockForUpdate()->findOrFail($target->getKey());

            if ($user->is_active === $isActive) {
                return $user;
            }

            if (
                $user->role === UserRole::Admin
                && $user->is_active
                && ! $isActive
                && $activeAdministratorIds->count() === 1
            ) {
                throw new LastActiveAdministrator;
            }

            $user->is_active = $isActive;
            $user->updated_by = $actor->getKey();
            $user->save();

            $this->auditService->record(
                $isActive ? AuditEvent::UserActivated : AuditEvent::UserDeactivated,
                $actor,
                $user,
            );

            return $user;
        });
    }

    /**
     * @return Collection<int, int>
     */
    private function lockActiveAdministrators(): Collection
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id');
    }
}
