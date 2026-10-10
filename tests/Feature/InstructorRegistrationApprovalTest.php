<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorRegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_page_offers_student_and_teacher_roles(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Daftar Sebagai')
            ->assertSee('Siswa')
            ->assertSee('Guru')
            ->assertSee('memerlukan persetujuan Admin');
    }

    public function test_teacher_registration_creates_inactive_instructor_pending_admin_approval(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => Role::ROLE_INSTRUCTOR,
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Pendaftaran akun guru berhasil. Akun Anda menunggu persetujuan Admin sebelum dapat digunakan.');

        $teacher = User::where('email', 'guru.baru@example.com')->firstOrFail();

        $this->assertTrue($teacher->isInstructor());
        $this->assertFalse($teacher->is_active);
        $this->assertGuest();
    }

    public function test_public_registration_cannot_request_admin_role(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Unauthorized Admin',
            'email' => 'admin.request@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => Role::ROLE_ADMIN,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'admin.request@example.com']);
        $this->assertGuest();
    }

    public function test_pending_teacher_cannot_login_before_admin_approval(): void
    {
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $teacher = User::factory()->create([
            'role_id' => $instructorRole->id,
            'email' => 'pending.teacher@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => $teacher->email,
            'password' => 'password123',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_see_and_activate_pending_teacher(): void
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN)->firstOrFail();
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create([
            'role_id' => $instructorRole->id,
            'name' => 'Guru Menunggu',
            'email' => 'waiting.teacher@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pengajuan Akun Guru')
            ->assertSee('waiting.teacher@example.com')
            ->assertSee('Aktifkan Guru');

        $this->actingAs($admin)
            ->post(route('admin.users.activate-instructor', $teacher))
            ->assertRedirect();

        $teacher->refresh();

        $this->assertTrue($teacher->isInstructor());
        $this->assertTrue($teacher->is_active);
    }
}
