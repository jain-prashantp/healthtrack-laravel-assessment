<?php

namespace App\Services;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResilientCacheService
{
    public const FORCE_REFRESH_ATTRIBUTE = 'force_refresh_cache';

    private const PRIMARY_STORE = 'redis';

    private const FALLBACK_STORE = 'database';

    /**
     * @return array{hit: bool, value: mixed, store: string}
     */
    public function read(string $key, ?string $preferredStore = null): array
    {
        $preferredStore ??= self::PRIMARY_STORE;

        if ($this->shouldForceRefresh()) {
            return [
                'hit' => false,
                'value' => null,
                'store' => $preferredStore,
            ];
        }

        try {
            $value = Cache::store($preferredStore)->get($key);

            return [
                'hit' => $value !== null,
                'value' => $value,
                'store' => $preferredStore,
            ];
        } catch (Throwable $exception) {
            $this->logFallbackWarning('read', $key, $preferredStore, $exception);

            return $this->readFromFallback($key);
        }
    }

    /**
     * @param  Closure(): mixed  $callback
     * @param  DateTimeInterface|DateInterval|int  $ttl
     */
    public function remember(string $key, DateTimeInterface|DateInterval|int $ttl, Closure $callback, ?string $preferredStore = null): mixed
    {
        $cached = $this->read($key, $preferredStore);

        if ($cached['hit']) {
            return $cached['value'];
        }

        $value = $callback();

        $this->put($key, $value, $ttl, $preferredStore);

        return $value;
    }

    /**
     * @param  DateTimeInterface|DateInterval|int  $ttl
     */
    public function put(string $key, mixed $value, DateTimeInterface|DateInterval|int $ttl, ?string $preferredStore = null): void
    {
        $preferredStore ??= self::PRIMARY_STORE;
        $primarySucceeded = false;

        try {
            Cache::store($preferredStore)->put($key, $value, $ttl);
            $primarySucceeded = true;
        } catch (Throwable $exception) {
            $this->logFallbackWarning('put', $key, $preferredStore, $exception);
        }

        if ($preferredStore === self::FALLBACK_STORE) {
            return;
        }

        try {
            Cache::store(self::FALLBACK_STORE)->put($key, $value, $ttl);
        } catch (Throwable $fallbackException) {
            if (! $primarySucceeded) {
                report($fallbackException);
            }
        }
    }

    public function forget(string $key, ?string $preferredStore = null): void
    {
        $preferredStore ??= self::PRIMARY_STORE;

        try {
            Cache::store($preferredStore)->forget($key);
        } catch (Throwable $exception) {
            $this->logFallbackWarning('forget', $key, $preferredStore, $exception);
        }

        if ($preferredStore === self::FALLBACK_STORE) {
            return;
        }

        try {
            Cache::store(self::FALLBACK_STORE)->forget($key);
        } catch (Throwable $fallbackException) {
            report($fallbackException);
        }
    }

    public function shouldForceRefresh(): bool
    {
        if (! app()->bound('request')) {
            return false;
        }

        return (bool) request()->attributes->get(self::FORCE_REFRESH_ATTRIBUTE, false);
    }

    /**
     * @return array{hit: bool, value: mixed, store: string}
     */
    private function readFromFallback(string $key): array
    {
        try {
            $value = Cache::store(self::FALLBACK_STORE)->get($key);

            return [
                'hit' => $value !== null,
                'value' => $value,
                'store' => self::FALLBACK_STORE,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'hit' => false,
                'value' => null,
                'store' => self::FALLBACK_STORE,
            ];
        }
    }

    private function logFallbackWarning(string $operation, string $key, string $preferredStore, Throwable $exception): void
    {
        Log::channel('api_calls')->warning('Cache fallback store used due to primary cache failure.', [
            'operation' => $operation,
            'cache_key' => $key,
            'preferred_store' => $preferredStore,
            'fallback_store' => self::FALLBACK_STORE,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
        ]);
    }
}
