<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_user_with_a_hashed_password(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Demo User',
            'email' => 'demo@example.test',
            'password' => 'demo-password-123',
            'password_confirmation' => 'demo-password-123',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'demo@example.test')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'demo@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('demo-password-123', $user->password));
    }

    public function test_registration_rejects_invalid_input(): void
    {
        $this->postJson('/api/register', ['email' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_returns_a_bearer_token(): void
    {
        $user = User::factory()->create(['password' => Hash::make('demo-password-123')]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'demo-password-123',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('demo-password-123')]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_removes_only_the_token_used_for_the_request(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current');
        $other = $user->createToken('other');

        $this->withToken($current->plainTextToken)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $current->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
    }
}
