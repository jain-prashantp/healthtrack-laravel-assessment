<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ExternalApis\CountryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrichPatientProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 180, 300];

    public function __construct(public int $userId)
    {
        $this->onConnection('redis');
        $this->onQueue('wellness');
    }

    public function handle(CountryService $countryService): void
    {
        $user = User::query()->find($this->userId);

        if (! $user || $user->role !== 'patient' || ! $user->country_code) {
            return;
        }

        $countryMetadata = $countryService->getCountryMetadata($user->country_code);

        if (! is_array($countryMetadata)) {
            return;
        }

        $user->patientProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'country_metadata' => $countryMetadata,
                'metadata_synced_at' => now(),
            ],
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('api_calls')->error('EnrichPatientProfileJob failed.', [
            'job' => self::class,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'user_id' => $this->userId,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }
}
