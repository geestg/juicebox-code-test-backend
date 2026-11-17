<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_queues_a_welcome_email(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Demo User',
            'email' => 'welcome@example.test',
            'password' => 'demo-password-123',
            'password_confirmation' => 'demo-password-123',
        ])->assertCreated();

        Queue::assertPushed(
            SendWelcomeEmail::class,
            fn (SendWelcomeEmail $job) => $job->userId === $response->json('data.id')
        );
    }

    public function test_command_queues_email_for_an_existing_user(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->artisan('welcome:send', ['user' => $user->id])
            ->assertSuccessful();

        Queue::assertPushed(
            SendWelcomeEmail::class,
            fn (SendWelcomeEmail $job) => $job->userId === $user->id
        );
    }

    public function test_command_rejects_an_unknown_user(): void
    {
        Queue::fake();

        $this->artisan('welcome:send', ['user' => 999999])
            ->expectsOutput('User not found.')
            ->assertExitCode(1);

        Queue::assertNothingPushed();
    }
}
