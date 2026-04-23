<?php

namespace App\Http\Requests\Api\V1\Patient;

use App\Http\Requests\Api\ApiFormRequest;

class UpdatePatientProfileRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => is_string($this->country_code) ? strtoupper($this->country_code) : $this->country_code,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
        ];
    }
}
