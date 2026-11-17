<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_list_requires_authentication(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_user_list_is_paginated(): void
    {
        $viewer = User::factory()->create();
        User::factory()->count(2)->create();
        Sanctum::actingAs($viewer);

        $this->getJson('/api/users?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 3)
            ->assertJsonMissingPath('data.0.email');
    }

    public function test_user_detail_returns_only_public_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertExactJson(['data' => $user->only('id', 'name')]);
    }

    public function test_missing_user_returns_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/users/999999')->assertNotFound();
    }
}
