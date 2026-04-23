<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Patient\StorePatientMedicationRequest;
use App\Jobs\FetchDrugInfoJob;
use App\Models\PatientMedication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientMedicationController extends ApiController
{
    public function store(StorePatientMedicationRequest $request): JsonResponse
    {
        Gate::authorize('create', PatientMedication::class);

        $medication = PatientMedication::create([
            'patient_id' => $request->user()->id,
            'drug_name' => $request->validated('drug_name'),
            'dosage' => $request->validated('dosage'),
            'frequency' => $request->validated('frequency'),
            'start_date' => $request->validated('start_date'),
            'end_date' => $request->validated('end_date'),
            'is_active' => true,
            'fda_warnings' => null,
            'fda_data_fetched_at' => null,
        ]);

        FetchDrugInfoJob::dispatch($medication->id);

        return $this->successResponse([
            'medication' => $medication,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PatientMedication::class);

        $medications = $request->user()
            ->patientMedications()
            ->where('is_active', true)
            ->orderBy('drug_name')
            ->get();

        return $this->successResponse([
            'medications' => $medications,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $medication = PatientMedication::query()->find($id);

        if (! $medication) {
            return $this->errorResponse([
                'medication' => ['Medication not found.'],
            ], 404);
        }

        Gate::authorize('delete', $medication);

        $medication->update([
            'is_active' => false,
        ]);

        return $this->successResponse([
            'medication' => $medication->fresh(),
        ]);
    }
}
