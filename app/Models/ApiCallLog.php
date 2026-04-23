<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCallLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'api_name',
        'endpoint',
        'method',
        'request_params',
        'response_status',
        'response_time_ms',
        'was_cached',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_params' => 'array',
            'response_status' => 'integer',
            'response_time_ms' => 'integer',
            'was_cached' => 'boolean',
        ];
    }
}
