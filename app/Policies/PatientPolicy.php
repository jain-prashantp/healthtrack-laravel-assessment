<?php

namespace App\Policies;

use App\Models\User;

class PatientPolicy
{
    public function view(User $user, User $patient): bool
    {
        return $user->role === 'patient' && $user->is($patient);
    }

    public function update(User $user, User $patient): bool
    {
        return $user->role === 'patient' && $user->is($patient);
    }
}
