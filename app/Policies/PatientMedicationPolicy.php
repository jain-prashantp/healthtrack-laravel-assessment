<?php

namespace App\Policies;

use App\Models\PatientMedication;
use App\Models\User;

class PatientMedicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'patient';
    }

    public function view(User $user, PatientMedication $medication): bool
    {
        return $user->role === 'patient' && $medication->patient_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'patient';
    }

    public function delete(User $user, PatientMedication $medication): bool
    {
        return $user->role === 'patient' && $medication->patient_id === $user->id;
    }
}
