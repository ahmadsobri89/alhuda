<?php

namespace Tests\Feature;

use App\Models\SecurityPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_settings(): void
    {
        $this->actingAs($this->user)->get('/settings')->assertOk();
    }

    // ── User Management ──────────────────────────────────────────────────────

    public function test_can_create_new_user(): void
    {
        $this->actingAs($this->user)
            ->post('/settings/users', [
                'name'     => 'Dr. Azlan',
                'email'    => 'azlan@klinik.test',
                'role'     => 'doctor',
                'status'   => 'active',
                'password' => 'password123',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'azlan@klinik.test',
            'role'  => 'doctor',
        ]);
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@klinik.test']);

        $this->actingAs($this->user)
            ->post('/settings/users', [
                'name'     => 'Another User',
                'email'    => 'duplicate@klinik.test',
                'role'     => 'nurse',
                'status'   => 'active',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_role_must_be_valid(): void
    {
        $this->actingAs($this->user)
            ->post('/settings/users', [
                'name'     => 'Test User',
                'email'    => 'test@klinik.test',
                'role'     => 'superuser',
                'status'   => 'active',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $this->actingAs($this->user)
            ->post('/settings/users', [
                'name'     => 'Test User',
                'email'    => 'test2@klinik.test',
                'role'     => 'nurse',
                'status'   => 'active',
                'password' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_can_update_user(): void
    {
        $target = User::factory()->create(['role' => 'nurse']);

        $this->actingAs($this->user)
            ->put("/settings/users/{$target->id}", [
                'name'   => 'Updated Name',
                'email'  => $target->email,
                'role'   => 'pharmacist',
                'status' => 'active',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'pharmacist']);
    }

    public function test_can_update_user_without_changing_password(): void
    {
        $target          = User::factory()->create();
        $originalPassword = $target->password;

        $this->actingAs($this->user)
            ->put("/settings/users/{$target->id}", [
                'name'   => 'New Name',
                'email'  => $target->email,
                'role'   => $target->role,
                'status' => 'active',
            ])
            ->assertSessionHas('success');

        $this->assertEquals($originalPassword, $target->fresh()->password);
    }

    public function test_can_delete_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->delete("/settings/users/{$target->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    // ── Security Policies ────────────────────────────────────────────────────

    public function test_can_update_security_policies(): void
    {
        $policy = SecurityPolicy::factory()->create(['enabled' => true]);

        $this->actingAs($this->user)
            ->put('/settings/policies', [
                'policies' => [
                    ['id' => $policy->id, 'enabled' => false],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('security_policies', ['id' => $policy->id, 'enabled' => false]);
    }

    public function test_policy_id_must_exist(): void
    {
        $this->actingAs($this->user)
            ->put('/settings/policies', [
                'policies' => [
                    ['id' => 9999, 'enabled' => true],
                ],
            ])
            ->assertSessionHasErrors('policies.0.id');
    }

    public function test_policies_array_is_required(): void
    {
        $this->actingAs($this->user)
            ->put('/settings/policies', [])
            ->assertSessionHasErrors('policies');
    }
}
