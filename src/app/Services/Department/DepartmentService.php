<?php

namespace App\Services\Department;

use App\Models\Department;
use App\Services\Audit\AuditService;
use Illuminate\Support\Str;

class DepartmentService
{
    public function __construct(private AuditService $auditService) {}

    public function create(array $data): Department
    {
        $department = Department::create([
            'name' => $data['name'],
            'code' => Str::upper($data['code']),
            'description' => $data['description'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->auditService->log('department.created', $department, ['name' => $department->name]);

        return $department;
    }

    public function update(Department $department, array $data): Department
    {
        $department->update([
            'name' => $data['name'],
            'code' => Str::upper($data['code']),
            'description' => $data['description'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $this->auditService->log('department.updated', $department, ['name' => $department->name]);

        return $department;
    }

    public function toggleStatus(Department $department): Department
    {
        $department->update(['is_active' => !$department->is_active]);

        $this->auditService->log(
            $department->is_active ? 'department.activated' : 'department.deactivated',
            $department
        );

        return $department;
    }
}
