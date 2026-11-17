<?php

use Illuminate\Foundation\Inspiring;
use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('welcome:send {user : User ID}', function (string $user): int {
    $id = filter_var($user, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id === false || ! User::whereKey($id)->exists()) {
        $this->error('User not found.');

        return 1;
    }

    SendWelcomeEmail::dispatch($id);
    $this->info('Welcome email queued.');

    return 0;
})->purpose('Queue a welcome email for an existing user');
