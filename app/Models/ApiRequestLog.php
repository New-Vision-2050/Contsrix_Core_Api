<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $table = 'api_request_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'method',
        'path',
        'route_name',
        'feature',
        'response_status',
        'duration_ms',
        'ip_address',
        'user_agent',
        'request_headers',
        'request_payload',
        'response_body',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'response_status' => 'integer',
            'duration_ms'     => 'integer',
        ];
    }
}
