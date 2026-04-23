<?php

namespace App\Services;

use App\Models\User;
use App\Models\WellnessAlert;
use App\Models\WellnessCheckin;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class WellnessSummaryService
{
    public function cacheKey(int $doctorId, int $patientId): string
    {
        return sprintf('wellness:summary:%d:%d', $doctorId, $patientId);
    }

    public function forget(int $doctorId, int $patientId): void
    {
        Cache::store('redis')->forget($this->cacheKey($doctorId, $patientId));
    }

    public function forgetForPatient(User $patient): void
    {
        if (! $patient->assigned_doctor_id) {
            return;
        }

        $this->forget((int) $patient->assigned_doctor_id, $patient->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(User $doctor, User $patient): array
    {
        return Cache::store('redis')->remember(
            $this->cacheKey($doctor->id, $patient->id),
            now()->addHour(),
            fn () => $this->buildSummary($doctor, $patient),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(User $doctor, User $patient): array
    {
        $periodStart = now()->subDays(30)->startOfDay();

        $checkins = $patient->wellnessCheckins()
            ->where('checked_in_at', '>=', $periodStart)
            ->orderByDesc('checked_in_at')
            ->get();

        $activeMedications = $patient->patientMedications()
            ->where('is_active', true)
            ->orderBy('drug_name')
            ->get();

        return [
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'city' => $patient->city,
                'country_code' => $patient->country_code,
                'timezone' => $patient->timezone,
                'is_active' => $patient->is_active,
            ],
            'checkins_last_30_days' => [
                'count' => $checkins->count(),
                'capped_at' => 30,
                'items' => $checkins
                    ->take(30)
                    ->map(fn (WellnessCheckin $checkin) => $this->transformCheckin($checkin))
                    ->values()
                    ->all(),
            ],
            'rolling_average_7_day' => $this->buildRollingAverage($checkins),
            'weather_correlation' => $this->buildWeatherCorrelation($checkins),
            'holiday_correlation' => $this->buildHolidayCorrelation($checkins),
            'active_medications' => $activeMedications
                ->map(fn ($medication) => [
                    'id' => $medication->id,
                    'drug_name' => $medication->drug_name,
                    'dosage' => $medication->dosage,
                    'frequency' => $medication->frequency,
                    'start_date' => $medication->start_date?->toDateString(),
                    'end_date' => $medication->end_date?->toDateString(),
                    'has_fda_warnings' => ! empty($medication->fda_warnings),
                    'warning_highlights' => array_keys($medication->fda_warnings ?? []),
                    'fda_warnings' => $medication->fda_warnings,
                    'fda_data_fetched_at' => $medication->fda_data_fetched_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'unread_alert_count' => WellnessAlert::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('is_read', false)
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCheckin(WellnessCheckin $checkin): array
    {
        return [
            'id' => $checkin->id,
            'checked_in_at' => $checkin->checked_in_at?->toIso8601String(),
            'mood_score' => $checkin->mood_score,
            'energy_level' => $checkin->energy_level,
            'symptoms' => $checkin->symptoms,
            'notes' => $checkin->notes,
            'is_holiday' => $checkin->is_holiday,
            'holiday_name' => $checkin->holiday_name,
            'weather_data' => $checkin->weather_data,
        ];
    }

    /**
     * @param  Collection<int, WellnessCheckin>  $checkins
     * @return array<int, array<string, mixed>>
     */
    private function buildRollingAverage(Collection $checkins): array
    {
        $period = CarbonPeriod::create(now()->subDays(29)->startOfDay(), now()->startOfDay());

        return collect($period)
            ->map(function (Carbon $day) use ($checkins): array {
                $windowStart = $day->copy()->subDays(6)->startOfDay();
                $windowEnd = $day->copy()->endOfDay();

                $windowCheckins = $checkins->filter(function (WellnessCheckin $checkin) use ($windowStart, $windowEnd): bool {
                    return $checkin->checked_in_at !== null
                        && $checkin->checked_in_at->between($windowStart, $windowEnd);
                });

                return [
                    'date' => $day->toDateString(),
                    'avg_mood' => $windowCheckins->isEmpty()
                        ? null
                        : round((float) $windowCheckins->avg('mood_score'), 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, WellnessCheckin>  $checkins
     * @return array<string, array<string, int|float|null>>
     */
    private function buildWeatherCorrelation(Collection $checkins): array
    {
        $rainyCheckins = $checkins->filter(function (WellnessCheckin $checkin): bool {
            $weatherCode = data_get($checkin->weather_data, 'weather_code', data_get($checkin->weather_data, 'weathercode'));

            return $weatherCode !== null && $this->isRainyWeatherCode((int) $weatherCode);
        });

        $clearCheckins = $checkins->filter(function (WellnessCheckin $checkin): bool {
            $weatherCode = data_get($checkin->weather_data, 'weather_code', data_get($checkin->weather_data, 'weathercode'));

            return $weatherCode !== null && $this->isClearWeatherCode((int) $weatherCode);
        });

        return [
            'rainy' => [
                'avg_mood' => $rainyCheckins->isEmpty() ? null : round((float) $rainyCheckins->avg('mood_score'), 2),
                'count' => $rainyCheckins->count(),
            ],
            'clear' => [
                'avg_mood' => $clearCheckins->isEmpty() ? null : round((float) $clearCheckins->avg('mood_score'), 2),
                'count' => $clearCheckins->count(),
            ],
        ];
    }

    /**
     * @param  Collection<int, WellnessCheckin>  $checkins
     * @return array<string, array<string, int|float|null>>
     */
    private function buildHolidayCorrelation(Collection $checkins): array
    {
        $holidayCheckins = $checkins->where('is_holiday', true);
        $regularCheckins = $checkins->where('is_holiday', false);

        return [
            'holiday' => [
                'avg_mood' => $holidayCheckins->isEmpty() ? null : round((float) $holidayCheckins->avg('mood_score'), 2),
                'count' => $holidayCheckins->count(),
            ],
            'regular' => [
                'avg_mood' => $regularCheckins->isEmpty() ? null : round((float) $regularCheckins->avg('mood_score'), 2),
                'count' => $regularCheckins->count(),
            ],
        ];
    }

    private function isRainyWeatherCode(int $weatherCode): bool
    {
        return in_array($weatherCode, [
            51, 53, 55, 56, 57,
            61, 63, 65, 66, 67,
            80, 81, 82, 95, 96, 99,
        ], true);
    }

    private function isClearWeatherCode(int $weatherCode): bool
    {
        return in_array($weatherCode, [0, 1], true);
    }
}
