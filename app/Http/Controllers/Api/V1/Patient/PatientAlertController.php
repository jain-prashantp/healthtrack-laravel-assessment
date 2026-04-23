<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Api\ApiController;
use App\Models\WellnessAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientAlertController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', WellnessAlert::class);

        $alerts = $request->user()
            ->patientAlerts()
            ->latest()
            ->get();

        return $this->successResponse([
            'alerts' => $alerts,
        ]);
    }
}
