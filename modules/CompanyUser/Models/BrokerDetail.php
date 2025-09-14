<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class BrokerDetail extends Model
{
    use UuidTrait;
    use BaseFilterable;

    public array $translatable = [];
    protected $table = 'broker_details';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "type",
        "company_representative_name",
        "registration_number",
        "company_name",
        "user_id",
        "company_id",
        "original_branch_id",
        "is_created_by_owner"

    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
