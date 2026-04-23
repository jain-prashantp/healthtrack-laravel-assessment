<?php

namespace Database\Seeders;

use App\Models\PatientMedication;
use App\Models\PatientProfile;
use App\Models\User;
use App\Models\WellnessCheckin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $doctorOne = $this->seedUser('doctor@healthtrack.test', $this->userAttributes('Doctor One', 'doctor'));

        $doctorTwo = $this->seedUser('doctor2@healthtrack.test', $this->userAttributes('Doctor Two', 'doctor'));

        $this->seedUser('admin@healthtrack.test', $this->userAttributes('HealthTrack Admin', 'admin'));

        $patientAssignments = [
            1 => $doctorOne->id,
            2 => $doctorOne->id,
            3 => $doctorOne->id,
            4 => $doctorTwo->id,
            5 => $doctorTwo->id,
        ];

        foreach ($patientAssignments as $index => $doctorId) {
            $patient = $this->seedUser(
                "patient{$index}@healthtrack.test",
                $this->patientAttributes($index, $doctorId)
            );

            PatientProfile::updateOrCreate(
                ['user_id' => $patient->id],
                [
                    'blood_type' => ['A+', 'B+', 'O+', 'AB+', 'O-'][$index - 1],
                    'date_of_birth' => Carbon::create(1990 + $index, min($index + 1, 12), min(10 + $index, 28)),
                    'emergency_contact_name' => "Emergency Contact {$index}",
                    'emergency_contact_phone' => '+91990000000'.$index,
                    'country_metadata' => null,
                    'metadata_synced_at' => null,
                ]
            );

            $patient->wellnessCheckins()->delete();
            $patient->patientMedications()->delete();

            $this->seedWellnessCheckins($patient, $index);
            $this->seedMedications($patient);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function seedUser(string $email, array $attributes): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill(['email' => $email] + $attributes)->save();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function userAttributes(string $name, string $role): array
    {
        return [
            'name' => $name,
            'password' => 'Password@123',
            'email_verified_at' => now(),
            'role' => $role,
            'country_code' => 'IN',
            'city' => 'Hyderabad',
            'latitude' => 17.385,
            'longitude' => 78.4867,
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
            'assigned_doctor_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function patientAttributes(int $index, int $doctorId): array
    {
        return [
            'name' => "Patient {$index}",
            'password' => 'Password@123',
            'email_verified_at' => now(),
            'role' => 'patient',
            'country_code' => 'IN',
            'city' => 'Hyderabad',
            'latitude' => 17.385,
            'longitude' => 78.4867,
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
            'assigned_doctor_id' => $doctorId,
        ];
    }

    private function seedWellnessCheckins(User $patient, int $patientIndex): void
    {
        $dayOffsets = [2, 4, 6, 9, 12, 15, 18, 22, 25, 29];
        $moodScores = [7, 6, 3, 8, 5, 2, 7, 4, 3, 6];
        $energyLevels = [6, 5, 4, 7, 6, 3, 7, 5, 4, 6];
        $symptomSets = [
            ['fatigue'],
            ['headache'],
            ['anxiety', 'fatigue'],
            ['low_motivation'],
            ['muscle_tension'],
            ['poor_sleep', 'fatigue'],
            ['headache', 'dehydration'],
            ['irritability'],
            ['anxiety'],
            ['fatigue', 'low_mood'],
        ];

        foreach ($dayOffsets as $position => $dayOffset) {
            WellnessCheckin::create([
                'patient_id' => $patient->id,
                'checked_in_at' => now()
                    ->subDays($dayOffset)
                    ->setTime(8 + (($patientIndex + $position) % 5), 0),
                'mood_score' => $moodScores[$position],
                'energy_level' => $energyLevels[$position],
                'symptoms' => $symptomSets[$position],
                'notes' => $moodScores[$position] <= 3
                    ? 'Patient reported a difficult day and requested closer monitoring.'
                    : 'Routine daily wellness check-in.',
                'is_holiday' => false,
                'holiday_name' => null,
                'weather_data' => null,
                'weather_fetched_at' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'HealthTrack Seeder',
            ]);
        }
    }

    private function seedMedications(User $patient): void
    {
        $medications = [
            [
                'drug_name' => 'Vitamin D3',
                'dosage' => '1000 IU',
                'frequency' => 'Once daily',
                'start_date' => now()->subMonths(6)->toDateString(),
            ],
            [
                'drug_name' => 'Metformin',
                'dosage' => '500 mg',
                'frequency' => 'Twice daily',
                'start_date' => now()->subMonths(4)->toDateString(),
            ],
            [
                'drug_name' => 'Omega-3',
                'dosage' => '1 capsule',
                'frequency' => 'Once daily',
                'start_date' => now()->subMonths(2)->toDateString(),
            ],
        ];

        foreach ($medications as $medication) {
            PatientMedication::create([
                'patient_id' => $patient->id,
                'drug_name' => $medication['drug_name'],
                'dosage' => $medication['dosage'],
                'frequency' => $medication['frequency'],
                'start_date' => $medication['start_date'],
                'end_date' => null,
                'is_active' => true,
                'fda_warnings' => null,
                'fda_data_fetched_at' => null,
            ]);
        }
    }
}
