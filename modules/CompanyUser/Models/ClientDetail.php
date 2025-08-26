<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\User\Models\User;

// use BasePackage\Shared\Traits\HasTranslations;

class ClientDetail extends Model
{
    use UuidTrait;
    use BaseFilterable;

    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];
    protected $table = 'client_details';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "type",
        "broker_id",
        "company_representative_name",
        "registration_number",
        "user_id",


    ];

    protected $casts = [
        'id' => 'string',
    ];


    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id');
    }
}
