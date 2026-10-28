<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_profile_and_appearance_settings(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profil & Tampilan', false)
            ->assertSee('Gelap')
            ->assertSee('Terang')
            ->assertSee('Pilih Foto');
    }

    public function test_user_can_update_name_bio_and_avatar(): void
    {
        Storage::fake('public');
        $user = $this->student();
        $avatar = UploadedFile::fake()->image('avatar.png', 128, 128);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Ruby Updated',
                'bio' => 'Belajar pemrograman dasar.',
                'avatar' => $avatar,
            ])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Ruby Updated', $user->name);
        $this->assertSame('Belajar pemrograman dasar.', $user->bio);
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);

        $this->actingAs($user)
            ->get(route('profile.avatar'))
            ->assertOk()
            ->assertJsonPath('name', 'Ruby Updated')
            ->assertJsonPath('avatar_url', Storage::disk('public')->url($user->avatar));
    }

    public function test_user_can_remove_existing_avatar(): void
    {
        Storage::fake('public');
        $user = $this->student();
        $path = UploadedFile::fake()->image('old-avatar.png', 64, 64)->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'bio' => $user->bio,
                'remove_avatar' => '1',
            ])
            ->assertSessionHas('success');

        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
    }

    private function student(): User
    {
        $role = Role::create(['name' => Role::ROLE_STUDENT, 'label' => 'Student']);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
            'name' => 'Ruby',
        ]);
    }
}
