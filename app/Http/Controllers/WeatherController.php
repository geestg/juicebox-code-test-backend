<?php

namespace App\Http\Controllers;

use App\Services\PerthWeather;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use UnexpectedValueException;

class WeatherController extends Controller
{
    public function __invoke(PerthWeather $weather): JsonResponse
    {
        try {
            return response()->json(['data' => $weather->current()]);
        } catch (ConnectionException | RequestException | UnexpectedValueException $error) {
            report($error);

            return response()->json(['message' => 'Weather data is temporarily unavailable.'], 503);
        }
    }
}
