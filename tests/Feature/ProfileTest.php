<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function adminOpd(): User
    {
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);

        return User::factory()->create()->assignRole(User::ROLE_ADMIN_OPD);
    }

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($this->adminOpd())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Ganti kata sandi')
            ->assertDontSee('Hapus akun');
    }

    public function test_profile_requires_admin_role(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/profile')->assertForbidden();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->adminOpd();

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Test User', 'email' => 'test@example.com'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->adminOpd();

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Test User', 'email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_update_cannot_change_role_or_opd(): void
    {
        $user = $this->adminOpd();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'X', 'email' => $user->email, 'opd_id' => 999, 'is_active' => false,
        ]);

        $user->refresh();
        $this->assertNull($user->opd_id);
        $this->assertTrue($user->is_active);
    }

    public function test_users_cannot_delete_their_own_account(): void
    {
        $user = $this->adminOpd();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
