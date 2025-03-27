<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Support\Facades\DB;
use phpseclib3\Common\Functions\Strings;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

// use BasePackage\Shared\Traits\HasTranslations;

class LoginWayStep extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;



    // use HasTranslations;
    // use SoftDeletes;

    protected $table = "login_way_steps";

    public array $translatable = [];

    public $incrementing = false;


    protected $fillable = [
        'driver_id',
        'login_way_id',
        "login_option",
        "login_option_alternatives",
        "order",
        "drivers",
    ];

    protected $casts = [
        'id' => "string",
        'login_way_id' => "string",
        "drivers" => "array",
        "login_option_alternatives" => "array",
    ];


}
