<?php

namespace Modules\ClientRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientRequestReceiverFrom extends Model
{
    protected $table = 'client_request_receiver_from';

    protected $fillable = [
        'name',
        'type',
    ];

    public function clientRequests(): HasMany
    {
        return $this->hasMany(ClientRequest::class);
    }
}
