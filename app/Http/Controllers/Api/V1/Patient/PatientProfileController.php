<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Patient\UpdatePatientProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $patient = $request->user()->load('patientProfile');

        Gate::authorize('patient.view-own-record', $patient);

        return $this->successResponse([
            'user' => $patient,
            'profile' => $patient->patientProfile,
        ]);
    }

    public function update(UpdatePatientProfileRequest $request): JsonResponse
    {
        $patient = $request->user();

        Gate::authorize('patient.update-own-record', $patient);

        $patient->update($request->safe()->only([
            'city',
            'country_code',
        ]));

        $patient->patientProfile()->firstOrCreate(['user_id' => $patient->id]);
        $patient->load('patientProfile');

        return $this->successResponse([
            'user' => $patient,
            'profile' => $patient->patientProfile,
        ]);
    }
}
