<?php

namespace Modules\ClientRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClientRequestService extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    public function clientRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            ClientRequest::class,
            'client_request_service_pivot',
            'client_request_service_id',
            'client_request_id'
        );
    }
}
