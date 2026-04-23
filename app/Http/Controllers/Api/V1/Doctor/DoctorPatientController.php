<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Services\WellnessSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DoctorPatientController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $patients = $request->user()
            ->assignedPatients()
            ->where('role', 'patient')
            ->with('patientProfile')
            ->orderBy('name')
            ->get();

        return $this->successResponse([
            'patients' => $patients,
        ]);
    }

    public function wellnessSummary(Request $request, int $id, WellnessSummaryService $wellnessSummaryService): JsonResponse
    {
        $patient = $this->findAssignedPatient($request, $id);

        if (! $patient) {
            return $this->errorResponse([
                'patient' => ['Patient not found.'],
            ], 404);
        }

        Gate::authorize('doctor.view-assigned-patient', $patient);

        return $this->successResponse([
            'summary' => $wellnessSummaryService->getSummary($request->user(), $patient),
        ]);
    }

    public function checkins(Request $request, int $id): JsonResponse
    {
        $patient = $this->findAssignedPatient($request, $id);

        if (! $patient) {
            return $this->errorResponse([
                'patient' => ['Patient not found.'],
            ], 404);
        }

        Gate::authorize('doctor.view-assigned-patient', $patient);

        $perPage = min(max((int) $request->integer('per_page', 10), 1), 50);

        $checkins = $patient->wellnessCheckins()
            ->latest('checked_in_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse($checkins, 'checkins');
    }

    public function medications(Request $request, int $id): JsonResponse
    {
        $patient = $this->findAssignedPatient($request, $id);

        if (! $patient) {
            return $this->errorResponse([
                'patient' => ['Patient not found.'],
            ], 404);
        }

        Gate::authorize('doctor.view-assigned-patient', $patient);

        $medications = $patient->patientMedications()
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->get();

        return $this->successResponse([
            'medications' => $medications,
        ]);
    }

    private function findAssignedPatient(Request $request, int $id): ?User
    {
        return $request->user()
            ->assignedPatients()
            ->where('role', 'patient')
            ->find($id);
    }
}
