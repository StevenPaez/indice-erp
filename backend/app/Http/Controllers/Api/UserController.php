<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Requests\Users\ChangeUserRoleRequest;
use App\Http\Requests\Users\ChangeUserStatusRequest;
use App\Http\Requests\Users\ListUsersRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController
{
    public function __construct(
        private readonly UserAdministrationService $userAdministrationService,
    ) {}

    public function index(ListUsersRequest $request): UserCollection
    {
        $filters = $request->validated();

        if (isset($filters['role'])) {
            $filters['role'] = UserRole::from($filters['role']);
        }

        if (array_key_exists('is_active', $filters)) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return UserCollection::make($this->userAdministrationService->list($filters));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = UserRole::from($data['role']);
        $data['is_active'] = $request->boolean('is_active');

        $user = $this->userAdministrationService->create($data, $request->user());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->userAdministrationService->updateIdentity(
            $user,
            $request->validated(),
            $request->user(),
        );

        return new UserResource($user);
    }

    public function changeRole(ChangeUserRoleRequest $request, User $user): UserResource
    {
        $user = $this->userAdministrationService->changeRole(
            $user,
            UserRole::from($request->validated('role')),
            $request->user(),
        );

        return new UserResource($user);
    }

    public function changeStatus(ChangeUserStatusRequest $request, User $user): UserResource
    {
        $user = $this->userAdministrationService->changeActiveStatus(
            $user,
            $request->boolean('is_active'),
            $request->user(),
        );

        return new UserResource($user);
    }
}
