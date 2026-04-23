<?php

namespace App\Policies;

use App\Models\User;

class DoctorPolicy
{
    public function view(User $user, User $patient): bool
    {
        return $user->role === 'doctor'
            && $patient->role === 'patient'
            && $patient->assigned_doctor_id === $user->id;
    }

    public function update(User $user, User $patient): bool
    {
        return $user->role === 'doctor'
            && $patient->role === 'patient'
            && $patient->assigned_doctor_id === $user->id;
    }
}
