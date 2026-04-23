<?php

namespace App\Jobs;

use App\Models\WellnessCheckin;
use App\Services\ExternalApis\HolidayService;
use App\Services\WellnessSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckHolidayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 120];

    public function __construct(public int $checkinId)
    {
        $this->onConnection('redis');
        $this->onQueue('wellness');
    }

    public function handle(HolidayService $holidayService, WellnessSummaryService $wellnessSummaryService): void
    {
        if (! config('wellness.holiday_stress_flag_enabled')) {
            return;
        }

        $checkin = WellnessCheckin::query()->with('patient')->find($this->checkinId);

        if (! $checkin || ! $checkin->patient) {
            return;
        }

        $holiday = $holidayService->getHolidayForDate(
            date: $checkin->checked_in_at,
            countryCode: $checkin->patient->country_code,
        );

        $checkin->update([
            'is_holiday' => $holiday !== null,
            'holiday_name' => $holiday['local_name'] ?? $holiday['name'] ?? null,
        ]);

        $wellnessSummaryService->forgetForPatient($checkin->patient);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('api_calls')->error('CheckHolidayJob failed.', [
            'job' => self::class,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'checkin_id' => $this->checkinId,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }
}
