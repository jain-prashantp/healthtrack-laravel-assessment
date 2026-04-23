<?php

namespace App\Http\Requests\Api\V1\Patient;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\WellnessCheckin;
use Illuminate\Support\Carbon;

class StorePatientCheckinRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'checked_in_at' => ['required', 'date', 'before_or_equal:now'],
            'mood_score' => [
                'required',
                'integer',
                'between:'.config('wellness.mood_scale_min').','.config('wellness.mood_scale_max'),
            ],
            'energy_level' => ['required', 'integer', 'between:1,10'],
            'symptoms' => ['nullable', 'array'],
            'symptoms.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_holiday' => ['nullable', 'boolean'],
            'holiday_name' => ['nullable', 'string', 'max:255', 'required_if:is_holiday,true'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if (! $user || $validator->errors()->has('checked_in_at')) {
                return;
            }

            $timezone = $user->timezone ?: config('app.timezone');
            $checkedInAt = Carbon::parse((string) $this->input('checked_in_at'));
            $checkinDay = $checkedInAt->copy()->setTimezone($timezone);
            $dayStart = $checkinDay->copy()->startOfDay()->utc();
            $dayEnd = $checkinDay->copy()->endOfDay()->utc();

            $dailyCheckinCount = WellnessCheckin::query()
                ->where('patient_id', $user->id)
                ->whereBetween('checked_in_at', [$dayStart, $dayEnd])
                ->count();

            if ($dailyCheckinCount >= config('wellness.check_in_max_per_day')) {
                $validator->errors()->add('checkins', 'Daily check-in limit reached.');
            }
        });
    }
}
