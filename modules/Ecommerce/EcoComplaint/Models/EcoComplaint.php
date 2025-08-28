<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoComplaint\Database\factories\EcoComplaintFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Ecommerce\EcoClient\Models\EcoClient;

//use BasePackage\Shared\Traits\HasTranslations;

class EcoComplaint extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'eco_client_id',
        'name',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): EcoComplaintFactory
    {
        return EcoComplaintFactory::new();
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(EcoClient::class, 'eco_client_id', 'id');
    }
}
