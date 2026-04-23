<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => null,
            'meta' => [
                'api_version' => 'v1',
                'pagination' => null,
                'cache_hit' => false,
            ],
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
