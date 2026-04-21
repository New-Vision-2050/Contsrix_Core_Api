<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use BasePackage\Shared\Traits\BaseFilterable;

use Stancl\Tenancy\Contracts;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;


class Domain extends Model  implements Contracts\Domain
{
    use HasFactory;
    use BaseFilterable;
    use SoftDeletes;
    
    protected $fillable =["domain","company_id"];


    public function tenant()
    {
        $this->belongsTo(config('tenancy.domain_model'), BelongsToTenant::$tenantIdColumn);

    }
}
