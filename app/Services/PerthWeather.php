<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use UnexpectedValueException;

class PerthWeather
{
    private const CACHE_KEY = 'weather:perth';

    public function current(): array
    {
        return Cache::get(self::CACHE_KEY) ?? $this->refresh();
    }

    public function refresh(): array
    {
        $current = Http::acceptJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => -31.95,
                'longitude' => 115.86,
                'timezone' => 'Australia/Perth',
                'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
            ])->throw()->json('current');

        $validator = Validator::make(is_array($current) ? $current : [], [
            'time' => ['required', 'string'],
            'temperature_2m' => ['required', 'numeric'],
            'relative_humidity_2m' => ['required', 'numeric', 'between:0,100'],
            'weather_code' => ['required', 'integer', 'min:0'],
            'wind_speed_10m' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            throw new UnexpectedValueException('Open-Meteo returned invalid current weather.');
        }

        $data = $validator->validated();
        $weather = [
            'city' => 'Perth',
            'timezone' => 'Australia/Perth',
            'time' => $data['time'],
            'temperature_c' => $data['temperature_2m'],
            'humidity_percent' => $data['relative_humidity_2m'],
            'weather_code' => $data['weather_code'],
            'wind_speed_kmh' => $data['wind_speed_10m'],
        ];

        Cache::put(self::CACHE_KEY, $weather, now()->addMinutes(15));

        return $weather;
    }
}
