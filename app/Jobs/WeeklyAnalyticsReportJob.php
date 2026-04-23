<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WellnessWeeklyStat;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeeklyAnalyticsReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [120, 300, 600];

    public function __construct()
    {
        $this->onConnection('redis');
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        $weekStart = CarbonImmutable::now()->startOfWeek()->subWeek();
        $weekEnd = $weekStart->endOfWeek();

        User::query()
            ->where('role', 'patient')
            ->where('is_active', true)
            ->with([
                'wellnessCheckins' => fn ($query) => $query
                    ->whereBetween('checked_in_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
                    ->orderBy('checked_in_at'),
            ])
            ->chunkById(100, function ($patients) use ($weekStart): void {
                foreach ($patients as $patient) {
                    $checkins = $patient->wellnessCheckins;

                    WellnessWeeklyStat::updateOrCreate(
                        [
                            'patient_id' => $patient->id,
                            'week_start_date' => $weekStart->toDateString(),
                        ],
                        [
                            'avg_mood_score' => $checkins->isEmpty()
                                ? null
                                : round((float) $checkins->avg('mood_score'), 2),
                            'total_checkins' => $checkins->count(),
                            'checkin_streak_days' => $this->calculateCheckinStreakDays($checkins),
                            'most_common_symptoms' => $this->mostCommonSymptoms($checkins),
                        ],
                    );
                }
            });
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('api_calls')->error('WeeklyAnalyticsReportJob failed.', [
            'job' => self::class,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }

    /**
     * @param  Collection<int, mixed>  $checkins
     */
    private function calculateCheckinStreakDays(Collection $checkins): int
    {
        $dates = $checkins
            ->filter(fn ($checkin) => $checkin->checked_in_at !== null)
            ->map(fn ($checkin) => $checkin->checked_in_at->toDateString())
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $longestStreak = 0;
        $previousDate = null;

        foreach ($dates as $date) {
            $currentDate = CarbonImmutable::parse($date);

            if ($previousDate && $previousDate->diffInDays($currentDate) === 1) {
                $streak++;
            } else {
                $streak = 1;
            }

            $longestStreak = max($longestStreak, $streak);
            $previousDate = $currentDate;
        }

        return $longestStreak;
    }

    /**
     * @param  Collection<int, mixed>  $checkins
     * @return array<int, array<string, int|string>>
     */
    private function mostCommonSymptoms(Collection $checkins): array
    {
        return $checkins
            ->flatMap(function ($checkin) {
                return collect($checkin->symptoms ?? [])
                    ->filter(fn ($symptom) => is_string($symptom) && $symptom !== '')
                    ->map(fn (string $symptom) => mb_strtolower(trim($symptom)));
            })
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn (int $count, string $symptom) => [
                'symptom' => $symptom,
                'count' => $count,
            ])
            ->values()
            ->all();
    }
}
