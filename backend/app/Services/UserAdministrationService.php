<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserAdministrationService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly UserAccessService $userAccessService,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, UserRole $role) => $query->where('role', $role))
            ->when(
                array_key_exists('is_active', $filters),
                fn ($query) => $query->where('is_active', $filters['is_active']),
            )
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_dir'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'is_active' => $data['is_active'],
                'must_change_password' => true,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);
            $user->save();

            $this->auditService->record(AuditEvent::UserCreated, $actor, $user);

            return $user;
        });
    }

    public function updateIdentity(User $target, array $data, User $actor): User
    {
        return DB::transaction(function () use ($target, $data, $actor): User {
            $user = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $user->name = $data['name'];
            $user->email = $data['email'];

            $changedFields = array_values(array_intersect(
                array_keys($user->getDirty()),
                ['name', 'email'],
            ));

            if ($changedFields === []) {
                return $user;
            }

            $user->updated_by = $actor->getKey();
            $user->save();

            $this->auditService->record(
                AuditEvent::UserUpdated,
                $actor,
                $user,
                ['fields' => $changedFields],
            );

            return $user;
        });
    }

    public function changeRole(User $target, UserRole $role, User $actor): User
    {
        return $this->userAccessService->changeRole($target, $role, $actor);
    }

    public function changeActiveStatus(User $target, bool $isActive, User $actor): User
    {
        return $this->userAccessService->changeActiveStatus($target, $isActive, $actor);
    }
}
