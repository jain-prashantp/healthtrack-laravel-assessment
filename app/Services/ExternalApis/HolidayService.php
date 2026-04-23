<?php

namespace App\Services\ExternalApis;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HolidayService extends BaseApiService
{
    protected function apiConfigKey(): string
    {
        return 'nager_date';
    }

    /**
     * @param  CarbonInterface|string  $date
     * @return array<string, mixed>|null
     */
    public function getHolidayForDate(CarbonInterface|string $date, ?string $countryCode = null): ?array
    {
        $holidayDate = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date);
        $normalizedCountryCode = Str::upper(
            trim($countryCode ?: (string) config('apis.nager_date.default_country_code'))
        );

        if ($normalizedCountryCode === '') {
            return null;
        }

        $payload = $this->get(
            endpoint: sprintf('PublicHolidays/%s/%s', $holidayDate->year, $normalizedCountryCode),
            cacheKey: sprintf('external-api:nager_date:%s:%s', $normalizedCountryCode, $holidayDate->year),
        );

        if (! is_array($payload)) {
            return null;
        }

        foreach ($payload as $holiday) {
            if (! is_array($holiday)) {
                continue;
            }

            if (($holiday['date'] ?? null) !== $holidayDate->toDateString()) {
                continue;
            }

            return [
                'date' => $holiday['date'] ?? null,
                'name' => $holiday['name'] ?? null,
                'local_name' => $holiday['localName'] ?? null,
                'country_code' => $holiday['countryCode'] ?? $normalizedCountryCode,
                'global' => $holiday['global'] ?? null,
                'types' => $holiday['types'] ?? [],
            ];
        }

        return null;
    }
}
