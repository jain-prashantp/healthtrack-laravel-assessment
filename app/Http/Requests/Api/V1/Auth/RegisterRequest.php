<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? strtolower($this->email) : $this->email,
            'country_code' => is_string($this->country_code) ? strtoupper($this->country_code) : $this->country_code,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'lowercase', 'unique:users,email'],
            'city' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }
}
