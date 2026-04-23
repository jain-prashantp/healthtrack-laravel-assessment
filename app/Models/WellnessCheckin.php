<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellnessCheckin extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'checked_in_at',
        'mood_score',
        'energy_level',
        'symptoms',
        'notes',
        'is_holiday',
        'holiday_name',
        'weather_data',
        'weather_fetched_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'mood_score' => 'integer',
            'energy_level' => 'integer',
            'symptoms' => 'array',
            'notes' => 'encrypted',
            'is_holiday' => 'boolean',
            'weather_data' => 'array',
            'weather_fetched_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
