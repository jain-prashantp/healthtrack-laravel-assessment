<?php

namespace App\Services\ExternalApis;

use App\Models\ApiCallLog;
use App\Services\ResilientCacheService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class BaseApiService
{
    private const NULL_CACHE_SENTINEL = '__healthtrack_null__';

    public function __construct(protected readonly ResilientCacheService $cache)
    {
    }

    abstract protected function apiConfigKey(): string;

    /**
     * @param  array<string, mixed>  $query
     */
    protected function get(
        string $endpoint,
        array $query = [],
        ?string $cacheKey = null,
        ?int $cacheTtl = null,
        bool $cacheNull = false
    ): ?array {
        $cacheKey ??= $this->cacheKey($endpoint, $query);
        $cacheTtl ??= $this->cacheTtl();

        $cached = $this->cache->read($cacheKey, 'redis');

        if ($cached['hit']) {
            $cachedPayload = $cached['value'];
            $isCachedNull = is_array($cachedPayload) && ($cachedPayload[self::NULL_CACHE_SENTINEL] ?? false);

            $this->logApiCall(
                endpoint: $endpoint,
                requestParams: $query,
                responseStatus: $isCachedNull ? 404 : 200,
                responseTimeMs: 0,
                wasCached: true,
                errorMessage: null,
            );

            return $isCachedNull || ! is_array($cachedPayload) ? null : $cachedPayload;
        }

        $startedAt = microtime(true);

        try {
            $response = $this->performGet($endpoint, $query);
            $responseTimeMs = $this->responseTimeMs($startedAt);

            if ($response->status() === 404) {
                if ($cacheNull) {
                    $this->cache->put($cacheKey, [self::NULL_CACHE_SENTINEL => true], $cacheTtl, 'redis');
                }

                $this->logApiCall(
                    endpoint: $endpoint,
                    requestParams: $query,
                    responseStatus: 404,
                    responseTimeMs: $responseTimeMs,
                    wasCached: false,
                    errorMessage: null,
                );

                return null;
            }

            $response->throw();

            $payload = $response->json();
            $normalizedPayload = is_array($payload) ? $payload : ['data' => $payload];

            $this->cache->put($cacheKey, $normalizedPayload, $cacheTtl, 'redis');

            $this->logApiCall(
                endpoint: $endpoint,
                requestParams: $query,
                responseStatus: $response->status(),
                responseTimeMs: $responseTimeMs,
                wasCached: false,
                errorMessage: null,
            );

            return $normalizedPayload;
        } catch (Throwable $exception) {
            $this->logApiCall(
                endpoint: $endpoint,
                requestParams: $query,
                responseStatus: $this->responseStatusFromException($exception),
                responseTimeMs: $this->responseTimeMs($startedAt),
                wasCached: false,
                errorMessage: $exception->getMessage(),
            );

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function cacheKey(string $endpoint, array $query = []): string
    {
        return sprintf(
            'external-api:%s:%s',
            $this->apiConfigKey(),
            sha1($endpoint.'|'.$this->normalizeQueryString($query)),
        );
    }

    protected function baseUrl(): string
    {
        return (string) config('apis.'.$this->apiConfigKey().'.base_url');
    }

    protected function timeout(): int
    {
        return (int) config('apis.'.$this->apiConfigKey().'.timeout', 10);
    }

    protected function cacheTtl(): int
    {
        return (int) config('apis.'.$this->apiConfigKey().'.cache_ttl', 3600);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function performGet(string $endpoint, array $query): Response
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = Http::acceptJson()
                    ->timeout($this->timeout())
                    ->baseUrl($this->baseUrl())
                    ->get(ltrim($endpoint, '/'), $query);
            } catch (ConnectionException $exception) {
                if ($attempt >= $this->maxAttempts()) {
                    throw $exception;
                }

                sleep($this->retryDelaySeconds($attempt));

                continue;
            }

            if (! $this->shouldRetryResponse($response) || $attempt >= $this->maxAttempts()) {
                return $response;
            }

            sleep($this->retryDelaySeconds($attempt));
        }
    }

    private function shouldRetryResponse(Response $response): bool
    {
        return in_array($response->status(), [429, 500, 502, 503, 504], true);
    }

    private function responseTimeMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    protected function maxAttempts(): int
    {
        return 3;
    }

    protected function retryDelaySeconds(int $attempt): int
    {
        return $attempt;
    }

    private function responseStatusFromException(Throwable $exception): ?int
    {
        if ($exception instanceof RequestException && $exception->response instanceof Response) {
            return $exception->response->status();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $requestParams
     */
    private function logApiCall(
        string $endpoint,
        array $requestParams,
        ?int $responseStatus,
        int $responseTimeMs,
        bool $wasCached,
        ?string $errorMessage
    ): void {
        try {
            ApiCallLog::create([
                'api_name' => $this->apiConfigKey(),
                'endpoint' => $endpoint,
                'method' => 'GET',
                'request_params' => $requestParams,
                'response_status' => $responseStatus,
                'response_time_ms' => $responseTimeMs,
                'was_cached' => $wasCached,
                'error_message' => $errorMessage,
            ]);
        } catch (Throwable $loggingException) {
            report($loggingException);
        }
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function normalizeQueryString(array $query): string
    {
        $normalized = Arr::sortRecursive($query);

        return http_build_query($normalized);
    }
}
