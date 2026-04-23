<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Admin\AssignDoctorRequest;
use App\Models\User;
use App\Services\WellnessSummaryService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends ApiController
{
    public function assignDoctor(
        AssignDoctorRequest $request,
        int $id,
        WellnessSummaryService $wellnessSummaryService
    ): JsonResponse {
        $patient = User::query()
            ->where('role', 'patient')
            ->find($id);

        if (! $patient) {
            return $this->errorResponse([
                'user' => ['Patient not found.'],
            ], 404);
        }

        $doctor = User::query()
            ->where('role', 'doctor')
            ->find($request->validated('doctor_id'));

        if (! $doctor) {
            return $this->errorResponse([
                'doctor' => ['Doctor not found.'],
            ], 404);
        }

        $previousDoctorId = $patient->assigned_doctor_id;

        $patient->update([
            'assigned_doctor_id' => $doctor->id,
        ]);

        if ($previousDoctorId) {
            $wellnessSummaryService->forget((int) $previousDoctorId, $patient->id);
        }

        $wellnessSummaryService->forget($doctor->id, $patient->id);

        return $this->successResponse([
            'patient' => $patient->fresh(['assignedDoctor']),
        ]);
    }
}
