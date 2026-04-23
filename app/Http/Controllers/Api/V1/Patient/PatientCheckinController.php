<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Patient\StorePatientCheckinRequest;
use App\Jobs\AnalyzeMoodAlertJob;
use App\Jobs\CheckHolidayJob;
use App\Jobs\EnrichCheckinWeatherJob;
use App\Models\WellnessCheckin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientCheckinController extends ApiController
{
    public function store(StorePatientCheckinRequest $request): JsonResponse
    {
        Gate::authorize('create', WellnessCheckin::class);

        $checkin = WellnessCheckin::create([
            'patient_id' => $request->user()->id,
            'checked_in_at' => $request->validated('checked_in_at'),
            'mood_score' => $request->validated('mood_score'),
            'energy_level' => $request->validated('energy_level'),
            'symptoms' => $request->validated('symptoms'),
            'notes' => $request->validated('notes'),
            'is_holiday' => $request->boolean('is_holiday'),
            'holiday_name' => $request->validated('holiday_name'),
            'weather_data' => null,
            'weather_fetched_at' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        EnrichCheckinWeatherJob::dispatch($checkin->id);
        CheckHolidayJob::dispatch($checkin->id);
        AnalyzeMoodAlertJob::dispatch($checkin->id);

        return $this->successResponse([
            'checkin' => $checkin,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', WellnessCheckin::class);

        $perPage = min(max((int) $request->integer('per_page', 10), 1), 50);

        $checkins = $request->user()
            ->wellnessCheckins()
            ->latest('checked_in_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse($checkins, 'checkins');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $checkin = WellnessCheckin::query()->find($id);

        if (! $checkin) {
            return $this->errorResponse([
                'checkin' => ['Check-in not found.'],
            ], 404);
        }

        Gate::authorize('view', $checkin);

        return $this->successResponse([
            'checkin' => $checkin,
        ]);
    }
}
