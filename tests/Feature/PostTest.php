<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_require_authentication(): void
    {
        $this->getJson('/api/posts')->assertUnauthorized();
    }

    public function test_posts_are_paginated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->createPost();
        $latest = $this->createPost();

        $this->getJson('/api/posts?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $latest->id)
            ->assertJsonPath('total', 2);
    }

    public function test_post_creation_uses_the_authenticated_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($owner);

        $this->postJson('/api/posts', [
            'title' => 'First post',
            'body' => 'Post content',
            'user_id' => $other->id,
        ])->assertCreated()->assertJsonPath('data.user_id', $owner->id)->assertJsonMissingPath('data.user');

        $this->assertDatabaseHas('posts', ['title' => 'First post', 'user_id' => $owner->id]);
    }

    public function test_post_creation_requires_title_and_body(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body']);
    }

    public function test_owner_can_patch_only_the_title(): void
    {
        $post = $this->createPost();
        Sanctum::actingAs($post->user);

        $this->patchJson("/api/posts/{$post->id}", ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated')
            ->assertJsonPath('data.body', 'Post content');
    }

    public function test_owner_can_delete_a_post(): void
    {
        $post = $this->createPost();
        Sanctum::actingAs($post->user);

        $this->deleteJson("/api/posts/{$post->id}")->assertNoContent();
        $this->assertModelMissing($post);
    }

    public function test_another_user_cannot_update_or_delete_a_post(): void
    {
        $post = $this->createPost();
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/posts/{$post->id}", ['title' => 'Changed'])
            ->assertForbidden();
        $this->deleteJson("/api/posts/{$post->id}")->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Original']);
    }

    private function createPost(): Post
    {
        $post = new Post(['title' => 'Original', 'body' => 'Post content']);
        $post->user()->associate(User::factory()->create());
        $post->save();

        return $post;
    }
}
