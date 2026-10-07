<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_updating_password_ends_sessions_on_other_devices(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['remember_token' => 'token-lama']);
        $sesi = fn (string $id, int $userId) => DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time(),
        ]);
        $sesi('perangkat-lain', $user->id);
        $sesi('pengguna-lain', User::factory()->create()->id);

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        // Sesi lain milik akun ini berakhir & cookie "ingat saya" lama tidak berlaku; sesi ini tetap login
        $this->assertDatabaseMissing('sessions', ['id' => 'perangkat-lain']);
        $this->assertDatabaseHas('sessions', ['id' => 'pengguna-lain']);
        $this->assertNotSame('token-lama', $user->fresh()->remember_token);
        $this->assertAuthenticatedAs($user);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }
}
