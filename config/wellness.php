<?php

return [
    'check_in_max_per_day' => (int) env('WELLNESS_CHECK_IN_MAX_PER_DAY', 3),
    'mood_scale_min' => (int) env('WELLNESS_MOOD_SCALE_MIN', 1),
    'mood_scale_max' => (int) env('WELLNESS_MOOD_SCALE_MAX', 10),
    'alert_mood_threshold' => (int) env('WELLNESS_ALERT_MOOD_THRESHOLD', 3),
    'weather_correlation_enabled' => env('WELLNESS_WEATHER_CORRELATION_ENABLED', true),
    'holiday_stress_flag_enabled' => env('WELLNESS_HOLIDAY_STRESS_FLAG_ENABLED', true),
    'analytics_retention_days' => (int) env('WELLNESS_ANALYTICS_RETENTION_DAYS', 90),
];
