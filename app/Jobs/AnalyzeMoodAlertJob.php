<?php

namespace App\Jobs;

use App\Models\WellnessAlert;
use App\Models\WellnessCheckin;
use App\Services\WellnessSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeMoodAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public function __construct(public int $checkinId)
    {
        $this->onConnection('redis');
        $this->onQueue('analytics');
    }

    public function handle(WellnessSummaryService $wellnessSummaryService): void
    {
        $checkin = WellnessCheckin::query()->with('patient')->find($this->checkinId);

        if (! $checkin || ! $checkin->patient) {
            return;
        }

        if ($checkin->mood_score > config('wellness.alert_mood_threshold')) {
            return;
        }

        $existingAlert = WellnessAlert::query()
            ->where('patient_id', $checkin->patient_id)
            ->where('doctor_id', $checkin->patient->assigned_doctor_id)
            ->where('alert_type', 'low_mood')
            ->get()
            ->first(function (WellnessAlert $alert) use ($checkin) {
                return ($alert->triggered_by['checkin_id'] ?? null) === $checkin->id;
            });

        if ($existingAlert) {
            return;
        }

        WellnessAlert::create([
            'patient_id' => $checkin->patient_id,
            'doctor_id' => $checkin->patient->assigned_doctor_id,
            'alert_type' => 'low_mood',
            'triggered_by' => [
                'checkin_id' => $checkin->id,
                'mood_score' => $checkin->mood_score,
                'checked_in_at' => $checkin->checked_in_at?->toIso8601String(),
            ],
            'severity' => $this->severityForMood($checkin->mood_score),
            'is_read' => false,
            'read_at' => null,
        ]);

        $wellnessSummaryService->forgetForPatient($checkin->patient);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('api_calls')->error('AnalyzeMoodAlertJob failed.', [
            'job' => self::class,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'checkin_id' => $this->checkinId,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }

    private function severityForMood(int $moodScore): string
    {
        return match (true) {
            $moodScore <= 1 => 'high',
            $moodScore === 2 => 'medium',
            default => 'low',
        };
    }
}
