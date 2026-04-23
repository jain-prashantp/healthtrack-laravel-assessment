<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'lowercase'],
            'password' => ['required', 'string'],
        ];
    }
}
