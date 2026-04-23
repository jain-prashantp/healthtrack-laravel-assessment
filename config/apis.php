<?php

return [
    'open_meteo' => [
        'base_url' => env('OPEN_METEO_BASE_URL', 'https://api.open-meteo.com/v1'),
        'timeout' => (int) env('OPEN_METEO_TIMEOUT', 10),
        'cache_ttl' => (int) env('OPEN_METEO_CACHE_TTL', 3600),
    ],

    'nager_date' => [
        'base_url' => env('NAGER_DATE_BASE_URL', 'https://date.nager.at/api/v3'),
        'timeout' => (int) env('NAGER_DATE_TIMEOUT', 10),
        'cache_ttl' => (int) env('NAGER_DATE_CACHE_TTL', 86400),
        'default_country_code' => env('NAGER_DATE_DEFAULT_COUNTRY_CODE', 'US'),
    ],

    'open_fda' => [
        'base_url' => env('OPEN_FDA_BASE_URL', 'https://api.fda.gov'),
        'timeout' => (int) env('OPEN_FDA_TIMEOUT', 15),
        'cache_ttl' => (int) env('OPEN_FDA_CACHE_TTL', 43200),
        'rate_limit_per_minute' => (int) env('OPEN_FDA_RATE_LIMIT_PER_MINUTE', 240),
    ],

    'rest_countries' => [
        'base_url' => env('REST_COUNTRIES_BASE_URL', 'https://restcountries.com/v3.1'),
        'timeout' => (int) env('REST_COUNTRIES_TIMEOUT', 10),
        'cache_ttl' => (int) env('REST_COUNTRIES_CACHE_TTL', 604800),
    ],
];
