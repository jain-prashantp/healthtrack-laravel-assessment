<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\ApiCallLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminSystemController extends ApiController
{
    public function apiLogs(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $logs = ApiCallLog::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse($logs, 'api_logs');
    }

    public function queueStats(): JsonResponse
    {
        return $this->successResponse([
            'queue_stats' => [
                'queue_connection' => config('queue.default'),
                'failed_jobs_count' => $this->failedJobsCount(),
                'queue_sizes' => [
                    'wellness' => $this->redisQueueSize('wellness'),
                    'analytics' => $this->redisQueueSize('analytics'),
                    'notifications' => $this->redisQueueSize('notifications'),
                ],
                'database_jobs_count' => $this->databaseJobsCount(),
            ],
        ]);
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function databaseJobsCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')->count();
    }

    private function redisQueueSize(string $queueName): ?int
    {
        try {
            $connectionName = (string) config('queue.connections.redis.connection', 'default');

            return (int) Redis::connection($connectionName)->llen('queues:'.$queueName);
        } catch (Throwable) {
            return null;
        }
    }
}
