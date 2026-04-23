<?php

namespace App\Policies;

use App\Models\PatientProfile;
use App\Models\User;

class PatientProfilePolicy
{
    public function view(User $user, PatientProfile $profile): bool
    {
        return $user->role === 'patient' && $profile->user_id === $user->id;
    }

    public function update(User $user, PatientProfile $profile): bool
    {
        return $user->role === 'patient' && $profile->user_id === $user->id;
    }
}
