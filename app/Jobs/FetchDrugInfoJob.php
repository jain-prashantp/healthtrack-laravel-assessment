<?php

namespace App\Jobs;

use App\Models\PatientMedication;
use App\Services\ExternalApis\DrugInfoService;
use App\Services\WellnessSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchDrugInfoJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [120, 300, 600];

    public int $uniqueFor = 43200;

    public function __construct(public int $medicationId)
    {
        $this->onConnection('redis');
        $this->onQueue('wellness');
    }

    public function handle(DrugInfoService $drugInfoService, WellnessSummaryService $wellnessSummaryService): void
    {
        $medication = PatientMedication::query()->with('patient')->find($this->medicationId);

        if (! $medication || ! $medication->patient) {
            return;
        }

        $drugInfo = $drugInfoService->getDrugWarnings($medication->drug_name);

        $medication->update([
            'fda_warnings' => $drugInfo['warnings'] ?? null,
            'fda_data_fetched_at' => now(),
        ]);

        $wellnessSummaryService->forgetForPatient($medication->patient);
    }

    public function uniqueId(): string
    {
        return (string) $this->medicationId;
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('api_calls')->error('FetchDrugInfoJob failed.', [
            'job' => self::class,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'medication_id' => $this->medicationId,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }
}
