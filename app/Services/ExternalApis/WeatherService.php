<?php

namespace App\Services\ExternalApis;

class WeatherService extends BaseApiService
{
    /**
     * @var list<string>
     */
    private const CURRENT_FIELDS = [
        'temperature_2m',
        'relative_humidity_2m',
        'uv_index',
        'apparent_temperature',
        'is_day',
        'precipitation',
        'weather_code',
        'cloud_cover',
        'pressure_msl',
        'surface_pressure',
        'wind_speed_10m',
        'wind_direction_10m',
        'wind_gusts_10m',
    ];

    protected function apiConfigKey(): string
    {
        return 'open_meteo';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCurrentWeather(float $latitude, float $longitude, ?string $timezone = null): ?array
    {
        $query = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => implode(',', self::CURRENT_FIELDS),
            'timezone' => $timezone ?: 'auto',
            'temperature_unit' => 'celsius',
            'wind_speed_unit' => 'kmh',
            'precipitation_unit' => 'mm',
        ];

        $payload = $this->get(
            endpoint: 'forecast',
            query: $query,
            cacheKey: sprintf(
                'external-api:open_meteo:%s:%s:%s',
                round($latitude, 4),
                round($longitude, 4),
                $timezone ?: 'auto',
            ),
        );

        if (! is_array($payload)) {
            return null;
        }

        return [
            'source' => 'open_meteo',
            'latitude' => data_get($payload, 'latitude'),
            'longitude' => data_get($payload, 'longitude'),
            'timezone' => data_get($payload, 'timezone'),
            'timezone_abbreviation' => data_get($payload, 'timezone_abbreviation'),
            'elevation' => data_get($payload, 'elevation'),
            'observed_at' => data_get($payload, 'current.time'),
            'temperature_2m' => data_get($payload, 'current.temperature_2m'),
            'relative_humidity_2m' => data_get($payload, 'current.relative_humidity_2m'),
            'uv_index' => data_get($payload, 'current.uv_index'),
            'weather_code' => data_get($payload, 'current.weather_code'),
            'weathercode' => data_get($payload, 'current.weather_code'),
            'temperature_c' => data_get($payload, 'current.temperature_2m'),
            'relative_humidity_percent' => data_get($payload, 'current.relative_humidity_2m'),
            'apparent_temperature_c' => data_get($payload, 'current.apparent_temperature'),
            'is_day' => data_get($payload, 'current.is_day'),
            'precipitation_mm' => data_get($payload, 'current.precipitation'),
            'cloud_cover_percent' => data_get($payload, 'current.cloud_cover'),
            'pressure_msl_hpa' => data_get($payload, 'current.pressure_msl'),
            'surface_pressure_hpa' => data_get($payload, 'current.surface_pressure'),
            'wind_speed_10m_kmh' => data_get($payload, 'current.wind_speed_10m'),
            'wind_direction_10m_degrees' => data_get($payload, 'current.wind_direction_10m'),
            'wind_gusts_10m_kmh' => data_get($payload, 'current.wind_gusts_10m'),
        ];
    }
}
