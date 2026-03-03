<?php

namespace Modules\ClientRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientRequestType extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    public function clientRequests(): HasMany
    {
        return $this->hasMany(ClientRequest::class);
    }
}
