<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Doctor\MarkDoctorAlertReadRequest;
use App\Models\WellnessAlert;
use App\Services\WellnessSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DoctorAlertController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $alerts = $request->user()
            ->doctorAlerts()
            ->where('is_read', false)
            ->with('patient:id,name,email,city,country_code,assigned_doctor_id')
            ->latest()
            ->get();

        return $this->successResponse([
            'alerts' => $alerts,
        ]);
    }

    public function markRead(
        MarkDoctorAlertReadRequest $request,
        int $id,
        WellnessSummaryService $wellnessSummaryService
    ): JsonResponse {
        $alert = WellnessAlert::query()
            ->with('patient')
            ->where('doctor_id', $request->user()->id)
            ->find($id);

        if (! $alert || ! $alert->patient) {
            return $this->errorResponse([
                'alert' => ['Alert not found.'],
            ], 404);
        }

        Gate::authorize('doctor.update-assigned-patient', $alert->patient);

        $alert->update([
            'is_read' => true,
            'read_at' => $alert->read_at ?? now(),
        ]);

        $wellnessSummaryService->forget($request->user()->id, $alert->patient_id);

        return $this->successResponse([
            'alert' => $alert->fresh(['patient']),
        ]);
    }
}
