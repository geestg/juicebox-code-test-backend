<?php

namespace Tests\Feature;

use App\Jobs\RefreshWeather;
use App\Services\PerthWeather;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    public function test_weather_returns_perth_conditions_and_caches_them_for_fifteen_minutes(): void
    {
        Http::fakeSequence()
            ->push($this->forecast(24.5))
            ->push($this->forecast(25.0));

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.city', 'Perth')
            ->assertJsonPath('data.timezone', 'Australia/Perth')
            ->assertJsonPath('data.temperature_c', 24.5);

        $this->travel(14)->minutes();
        $this->getJson('/api/weather')->assertJsonPath('data.temperature_c', 24.5);
        Http::assertSentCount(1);

        $this->travel(2)->minutes();
        $this->getJson('/api/weather')->assertJsonPath('data.temperature_c', 25);
        Http::assertSentCount(2);
    }

    public function test_weather_returns_503_when_the_provider_fails(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->getJson('/api/weather')
            ->assertStatus(503)
            ->assertExactJson(['message' => 'Weather data is temporarily unavailable.']);
    }

    public function test_weather_rejects_an_incomplete_provider_response(): void
    {
        Http::fake(['*' => Http::response(['current' => ['time' => '2026-11-17T10:00']])]);

        $this->getJson('/api/weather')->assertStatus(503);
    }

    public function test_refresh_job_replaces_a_cached_forecast(): void
    {
        Http::fakeSequence()
            ->push($this->forecast(24.5))
            ->push($this->forecast(26.5));

        $weather = app(PerthWeather::class);
        $weather->current();

        (new RefreshWeather)->handle($weather);

        $this->getJson('/api/weather')->assertOk()->assertJsonPath('data.temperature_c', 26.5);
        Http::assertSentCount(2);
    }

    private function forecast(float $temperature): array
    {
        return ['current' => [
            'time' => '2026-11-17T10:00',
            'temperature_2m' => $temperature,
            'relative_humidity_2m' => 62,
            'weather_code' => 0,
            'wind_speed_10m' => 13.4,
        ]];
    }
}
