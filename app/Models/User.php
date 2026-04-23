<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'country_code',
        'city',
        'latitude',
        'longitude',
        'timezone',
        'is_active',
        'assigned_doctor_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'password' => 'hashed',
        ];
    }

    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'assigned_doctor_id');
    }

    public function assignedPatients(): HasMany
    {
        return $this->hasMany(self::class, 'assigned_doctor_id');
    }

    public function patientProfile(): HasOne
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function wellnessCheckins(): HasMany
    {
        return $this->hasMany(WellnessCheckin::class, 'patient_id');
    }

    public function patientMedications(): HasMany
    {
        return $this->hasMany(PatientMedication::class, 'patient_id');
    }

    public function patientAlerts(): HasMany
    {
        return $this->hasMany(WellnessAlert::class, 'patient_id');
    }

    public function doctorAlerts(): HasMany
    {
        return $this->hasMany(WellnessAlert::class, 'doctor_id');
    }

    public function weeklyStats(): HasMany
    {
        return $this->hasMany(WellnessWeeklyStat::class, 'patient_id');
    }
}
