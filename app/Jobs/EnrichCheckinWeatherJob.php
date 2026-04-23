<?php

namespace App\Jobs;

use App\Models\WellnessCheckin;
use App\Services\ExternalApis\WeatherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrichCheckinWeatherJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    public int $uniqueFor = 3600;

    public function __construct(public int $checkinId)
    {
        $this->onConnection('redis');
        $this->onQueue('wellness');
    }

    public function handle(WeatherService $weatherService): void
    {
        if (! config('wellness.weather_correlation_enabled')) {
            return;
        }

        $checkin = WellnessCheckin::query()->with('patient')->find($this->checkinId);

        if (! $checkin || ! $checkin->patient) {
            return;
        }

        if ($checkin->patient->latitude === null || $checkin->patient->longitude === null) {
            return;
        }

        $weatherData = $weatherService->getCurrentWeather(
            latitude: (float) $checkin->patient->latitude,
            longitude: (float) $checkin->patient->longitude,
            timezone: $checkin->patient->timezone,
        );

        if (! is_array($weatherData)) {
            return;
        }

        $checkin->update([
            'weather_data' => $weatherData,
            'weather_fetched_at' => now(),
        ]);
    }

    public function uniqueId(): string
    {
        return (string) $this->checkinId;
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('api_calls')->error('EnrichCheckinWeatherJob failed.', [
            'job' => self::class,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'checkin_id' => $this->checkinId,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }
}
