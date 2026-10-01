<?php

namespace Modules\Access\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Contracts\QuotaManagerContract;
use Modules\Core\Enums\UserStatus;

class TenantUserService
{
    public function __construct(
        protected QuotaManagerContract $quotaManager
    ) {}

    /**
     * List users in current tenant context with optional search.
     *
     * @return Collection<int, User>
     */
    public function listUsers(?string $search = null): Collection
    {
        return User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            })
            ->with(['roles.permissions', 'media'])
            ->latest()
            ->get();
    }

    /**
     * Create a new user inside the tenant with strict quota enforcement.
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     role?: string,
     *     job_title?: string|null,
     *     phone?: string|null
     * } $data
     *
     * @throws ValidationException
     */
    public function createUser(array $data, ?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::current();

        if ($tenant && ! $this->quotaManager->canAddUser($tenant)) {
            $userLimit = $this->quotaManager->getUserLimit($tenant);
            throw ValidationException::withMessages([
                'email' => ["Plan limit reached. Your plan allows a maximum of {$userLimit} users. Please upgrade your subscription."],
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => UserStatus::Active->value,
        ]);

        $roleName = $data['role'] ?? TenantPermissions::ROLE_MEMBER;
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($roleName);
        }

        return $user;
    }

    /**
     * Update an existing tenant user.
     */
    public function updateUser(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? $user->status,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        if (! empty($data['role']) && method_exists($user, 'syncRoles')) {
            $user->syncRoles([$data['role']]);
        }

        return $user;
    }

    /**
     * Delete a user from tenant database.
     */
    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}
