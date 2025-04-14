<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ActivityLog\Database\factories\ActivityLogFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stevebauman\Location\Facades\Location;


//use BasePackage\Shared\Traits\HasTranslations;

class ActivityLog extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;
    //use SoftDeletes;

    public array $translatable = ["action"];
    protected $with = ["user"];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "action",
        'date',
        'user_id',
        'company_id',
        'action',
        "requestable_id",
        "requestable_type",
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function getDateAttribute($value) // get date with timezone user
    {
        $position = Location::get(request()->ip());

        $timezone = $position ? $position->timezone : "Asia/Riyadh";
        return Carbon::parse($value)->setTimezone($timezone)->format('Y-m-d H:i:s');
    }

    protected static function newFactory(): ActivityLogFactory
    {
        return ActivityLogFactory::new();
    }
    public function requestable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
