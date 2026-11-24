<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Departments
        $departments = [
            ['name' => 'Administration', 'code' => 'ADMIN', 'description' => 'Executive and office administration'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'People operations, recruitment, and policies'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Accounting, payroll, and budgeting'],
            ['name' => 'IT', 'code' => 'IT', 'description' => 'Infrastructure, security, and internal support'],
            ['name' => 'Development', 'code' => 'DEV', 'description' => 'Software engineering and product development'],
            ['name' => 'Sales', 'code' => 'SALES', 'description' => 'Business development and client relations'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Daily business operations and logistics'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }

        // 2. Seed Permissions
        $permissions = [
            // Dashboard & Audit
            ['name' => 'View Admin Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
            ['name' => 'View Audit Logs', 'slug' => 'audit.view', 'group' => 'audit'],
            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'group' => 'users'],
            ['name' => 'Toggle User Status', 'slug' => 'users.status', 'group' => 'users'],
            // Roles
            ['name' => 'Manage Roles & Permissions', 'slug' => 'roles.manage', 'group' => 'roles'],
            // Departments
            ['name' => 'View Departments', 'slug' => 'departments.view', 'group' => 'departments'],
            ['name' => 'Manage Departments', 'slug' => 'departments.manage', 'group' => 'departments'],
            // Documents
            ['name' => 'View Documents', 'slug' => 'documents.view', 'group' => 'documents'],
            ['name' => 'Upload Documents', 'slug' => 'documents.upload', 'group' => 'documents'],
            ['name' => 'Download Documents', 'slug' => 'documents.download', 'group' => 'documents'],
            ['name' => 'Delete Documents', 'slug' => 'documents.delete', 'group' => 'documents'],
            ['name' => 'Manage Document Permissions', 'slug' => 'documents.permissions', 'group' => 'documents'],
            ['name' => 'View Confidential Documents', 'slug' => 'documents.confidential', 'group' => 'documents'],
            ['name' => 'Manage Document Categories', 'slug' => 'categories.manage', 'group' => 'documents'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        // 3. Seed Roles & Attach Permissions
        $rolesData = [
            'super-admin' => ['name' => 'Super Admin', 'desc' => 'Full unrestricted system access'],
            'admin' => ['name' => 'Admin', 'desc' => 'Office administration and user management'],
            'hr-manager' => ['name' => 'HR Manager', 'desc' => 'HR policies, employee records, and HR documents'],
            'finance-manager' => ['name' => 'Finance Manager', 'desc' => 'Financial documents and audits'],
            'department-manager' => ['name' => 'Department Manager', 'desc' => 'Department team and document oversight'],
            'employee' => ['name' => 'Employee', 'desc' => 'Standard office employee access'],
        ];

        $allPermissionIds = Permission::pluck('id')->toArray();

        foreach ($rolesData as $slug => $info) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $info['name'], 'description' => $info['desc']]
            );

            if (in_array($slug, ['super-admin', 'admin'])) {
                $role->permissions()->sync($allPermissionIds);
            } elseif (in_array($slug, ['hr-manager', 'finance-manager', 'department-manager'])) {
                $mgrPerms = Permission::whereIn('slug', [
                    'dashboard.view', 'users.view', 'departments.view',
                    'documents.view', 'documents.upload', 'documents.download',
                    'documents.permissions', 'documents.confidential'
                ])->pluck('id')->toArray();
                $role->permissions()->sync($mgrPerms);
            } else {
                $empPerms = Permission::whereIn('slug', [
                    'dashboard.view', 'documents.view', 'documents.download'
                ])->pluck('id')->toArray();
                $role->permissions()->sync($empPerms);
            }
        }

        // 4. Seed Document Categories
        $categories = [
            'HR Policy',
            'Company Policy',
            'Finance',
            'Technical Documentation',
            'Project Documentation',
            'SOP',
            'Employee Handbook',
            'General Documents',
        ];

        foreach ($categories as $catName) {
            DocumentCategory::firstOrCreate(
                ['slug' => Str::slug($catName)],
                ['name' => $catName, 'is_active' => true]
            );
        }

        // 5. Create Initial Super Admin User
        $adminDept = Department::where('code', 'ADMIN')->first();
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@office.local'],
            [
                'employee_id' => 'EMP-0001',
                'name' => 'System Super Admin',
                'department_id' => $adminDept?->id,
                'designation' => 'Chief System Administrator',
                'password' => Hash::make('Admin@12345'),
                'is_active' => true,
            ]
        );

        if ($superAdminRole) {
            $adminUser->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
    }
}
