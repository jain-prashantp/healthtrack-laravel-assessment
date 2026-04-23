<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WellnessAlert;
use App\Services\WellnessSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyMissedCheckinAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 180, 300];

    public function __construct()
    {
        $this->onConnection('redis');
        $this->onQueue('notifications');
    }

    public function handle(WellnessSummaryService $wellnessSummaryService): void
    {
        $threshold = now()->subHours(48);

        User::query()
            ->where('role', 'patient')
            ->where('is_active', true)
            ->whereNotNull('assigned_doctor_id')
            ->with([
                'wellnessCheckins' => fn ($query) => $query
                    ->latest('checked_in_at')
                    ->limit(1),
            ])
            ->whereDoesntHave('wellnessCheckins', function ($query) use ($threshold): void {
                $query->where('checked_in_at', '>=', $threshold);
            })
            ->chunkById(100, function ($patients) use ($wellnessSummaryService, $threshold): void {
                foreach ($patients as $patient) {
                    $existingUnreadAlert = WellnessAlert::query()
                        ->where('doctor_id', $patient->assigned_doctor_id)
                        ->where('patient_id', $patient->id)
                        ->where('alert_type', 'missed_checkin')
                        ->where('is_read', false)
                        ->exists();

                    if ($existingUnreadAlert) {
                        continue;
                    }

                    $lastCheckin = $patient->wellnessCheckins->first();
                    $hoursSinceLastCheckin = $lastCheckin?->checked_in_at
                        ? (int) $lastCheckin->checked_in_at->diffInHours(now())
                        : null;

                    WellnessAlert::create([
                        'patient_id' => $patient->id,
                        'doctor_id' => $patient->assigned_doctor_id,
                        'alert_type' => 'missed_checkin',
                        'triggered_by' => [
                            'job' => self::class,
                            'checked_at' => now()->toIso8601String(),
                            'threshold_hours' => 48,
                            'threshold_started_at' => $threshold->toIso8601String(),
                            'last_checkin_at' => $lastCheckin?->checked_in_at?->toIso8601String(),
                            'hours_since_last_checkin' => $hoursSinceLastCheckin,
                        ],
                        'severity' => $this->severityForHours($hoursSinceLastCheckin),
                        'is_read' => false,
                        'read_at' => null,
                    ]);

                    $wellnessSummaryService->forgetForPatient($patient);
                }
            });
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('api_calls')->error('DailyMissedCheckinAlertJob failed.', [
            'job' => self::class,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }

    private function severityForHours(?int $hoursSinceLastCheckin): string
    {
        if ($hoursSinceLastCheckin === null || $hoursSinceLastCheckin >= 96) {
            return 'high';
        }

        if ($hoursSinceLastCheckin >= 72) {
            return 'medium';
        }

        return 'low';
    }
}
