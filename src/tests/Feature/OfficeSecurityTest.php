<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_login_and_view_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@office.local',
            'password' => 'Admin@12345',
        ]);

        $response->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertStatus(200);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Inactive Staff',
            'email' => 'inactive@office.local',
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@office.local',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unauthorized_employee_is_blocked_from_downloading_confidential_document(): void
    {
        Storage::fake('local');

        $hrDept = Department::where('code', 'HR')->first();
        $itDept = Department::where('code', 'IT')->first();
        $empRole = Role::where('slug', 'employee')->first();

        $hrUploader = User::create([
            'name' => 'HR Uploader',
            'email' => 'hr@office.local',
            'department_id' => $hrDept->id,
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $itEmployee = User::create([
            'name' => 'IT Staff',
            'email' => 'it@office.local',
            'department_id' => $itDept->id,
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $itEmployee->roles()->attach($empRole);

        $doc = Document::create([
            'title' => 'Top Secret HR Salary Sheet',
            'original_filename' => 'salary.pdf',
            'stored_filename' => 'secret.pdf',
            'storage_path' => 'private/documents/secret.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'checksum' => hash('sha256', 'dummy'),
            'department_id' => $hrDept->id,
            'confidentiality' => 'highly_confidential',
            'uploaded_by' => $hrUploader->id,
        ]);

        // IT Employee attempts to download HR's highly confidential file -> MUST BE 403
        $this->actingAs($itEmployee)
            ->get(route('documents.download', $doc))
            ->assertStatus(403);
    }
}
