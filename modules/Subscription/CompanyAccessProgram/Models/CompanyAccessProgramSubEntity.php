<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Models;

use Modules\Country\Models\Country;
use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Modules\SubEntity\Models\SubEntity;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyField\Models\CompanyField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscription\Package\Models\Package;

class CompanyAccessProgramSubEntity extends Model
{
    use HasFactory;
    use BaseFilterable;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'company_access_program_sub_entity';
    public $timestamps = false;

    protected $fillable = [
        'company_access_program_id',
        'sub_entity_id'
    ];

    protected $guarded=[];

    protected $casts = [

    ];


}
