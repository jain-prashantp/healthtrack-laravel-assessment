<?php

namespace App\Services\ExternalApis;

use Illuminate\Support\Str;

class CountryService extends BaseApiService
{
    protected function apiConfigKey(): string
    {
        return 'rest_countries';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCountryMetadata(string $countryCode): ?array
    {
        $normalizedCountryCode = Str::upper(trim($countryCode));

        if ($normalizedCountryCode === '') {
            return null;
        }

        $payload = $this->get(
            endpoint: 'alpha/'.$normalizedCountryCode,
            cacheKey: 'external-api:rest_countries:alpha:'.$normalizedCountryCode,
        );

        $country = is_array($payload[0] ?? null) ? $payload[0] : $payload;

        if (! is_array($country)) {
            return null;
        }

        return [
            'name' => data_get($country, 'name.common'),
            'official_name' => data_get($country, 'name.official'),
            'cca2' => data_get($country, 'cca2', $normalizedCountryCode),
            'cca3' => data_get($country, 'cca3'),
            'region' => data_get($country, 'region'),
            'subregion' => data_get($country, 'subregion'),
            'capital' => data_get($country, 'capital', []),
            'currencies' => data_get($country, 'currencies', []),
            'languages' => data_get($country, 'languages', []),
            'timezones' => data_get($country, 'timezones', []),
            'latlng' => data_get($country, 'latlng', []),
        ];
    }
}
