<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    protected function successResponse(array $data = [], int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $this->defaultMeta($meta),
            'errors' => [],
        ], $status);
    }

    /**
     * @param array<string, mixed> $errors
     * @param array<string, mixed>|null $data
     * @param array<string, mixed> $meta
     */
    protected function errorResponse(array $errors = [], int $status = 400, ?array $data = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'meta' => $this->defaultMeta($meta),
            'errors' => $errors,
        ], $status);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function defaultMeta(array $overrides = []): array
    {
        return array_merge([
            'api_version' => 'v1',
            'pagination' => null,
            'cache_hit' => false,
        ], $overrides);
    }
}
