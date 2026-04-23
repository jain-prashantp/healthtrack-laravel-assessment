<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WellnessCheckin;

class WellnessCheckinPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'patient';
    }

    public function view(User $user, WellnessCheckin $checkin): bool
    {
        return $user->role === 'patient' && $checkin->patient_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'patient';
    }
}
