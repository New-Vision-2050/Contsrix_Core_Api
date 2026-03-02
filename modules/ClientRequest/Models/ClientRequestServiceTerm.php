<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\TermServiceSetting\Models\TermServiceSetting;

class ClientRequestServiceTerm extends Model
{
    use HasFactory;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'client_request_service_term';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'client_request_id',
        'term_service_setting_id',
        'term_ids',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
        'client_request_id' => 'string',
        'company_id' => 'string',
        'term_ids' => 'array',
    ];

    public function clientRequest()
    {
        return $this->belongsTo(ClientRequest::class, 'client_request_id');
    }

    public function termServiceSetting()
    {
        return $this->belongsTo(TermServiceSetting::class, 'term_service_setting_id');
    }

    protected static function newFactory()
    {
        return \Modules\ClientRequest\Database\factories\ClientRequestServiceTermFactory::new();
    }
}
