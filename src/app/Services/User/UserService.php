<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private AuditService $auditService) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'employee_id' => $data['employee_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'department_id' => $data['department_id'] ?? null,
                'designation' => $data['designation'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (!empty($data['roles'])) {
                $user->roles()->sync($data['roles']);
            }

            $this->auditService->log('user.created', $user, [
                'email' => $user->email,
                'department_id' => $user->department_id,
            ]);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = [
                'employee_id' => $data['employee_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'department_id' => $data['department_id'] ?? null,
                'designation' => $data['designation'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ];

            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->update($payload);
            $user->roles()->sync($data['roles'] ?? []);

            $this->auditService->log('user.updated', $user, [
                'email' => $user->email,
                'department_id' => $user->department_id,
            ]);

            return $user;
        });
    }

    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);

        $this->auditService->log(
            $user->is_active ? 'user.activated' : 'user.deactivated',
            $user,
            ['email' => $user->email]
        );

        return $user;
    }
}
