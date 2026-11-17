<?php

namespace App\Jobs;

use App\Services\PerthWeather;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshWeather implements ShouldQueue
{
    use Queueable;

    public function handle(PerthWeather $weather): void
    {
        $weather->refresh();
    }
}
