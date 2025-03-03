<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Support\Facades\DB;
use phpseclib3\Common\Functions\Strings;

// use BasePackage\Shared\Traits\HasTranslations;

class LoginWayStep extends Model
{
    use UuidTrait;
    use BaseFilterable;

    // use HasTranslations;
    // use SoftDeletes;

    protected $table = "login_way_steps";

    public array $translatable = [];

    public $incrementing = false;


    protected $fillable = [
        'driver_id',
        'login_way_id',
        "login_option",
        "order",
        "drivers",
    ];

    protected $casts = [
        'id' => "string",
        'login_way_id' => "string",
        "drivers" => "array",
    ];


}
