<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WellnessAlert;

class WellnessAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'patient';
    }

    public function view(User $user, WellnessAlert $alert): bool
    {
        return $user->role === 'patient' && $alert->patient_id === $user->id;
    }
}
