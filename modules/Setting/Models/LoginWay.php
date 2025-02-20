<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class LoginWay extends Model
{
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';


    public $with = ['loginWaySteps'];

    protected $fillable = [
        "name",
        "company_id"
    ];
    protected $casts = [
        'id' => 'string',
    ];

    public function loginWaySteps()
    {
        return $this->hasMany(LoginWayStep::class, 'login_way_id', 'id');
    }
}
