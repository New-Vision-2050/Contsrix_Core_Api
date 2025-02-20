<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;

// use BasePackage\Shared\Traits\HasTranslations;

class LoginWayStep extends Model
{
    use UuidTrait;
    use BaseFilterable;

    // use HasTranslations;
    // use SoftDeletes;

    public $with = ['drivers'];

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'driver_id',
        'login_way_id',
        "login_option",
        "order",
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'login_way_steps_drivers', 'login_way_step_id', 'driver_id');
    }

    public function delete()
    {
        $this->drivers()->detach();
        parent::delete();
    }


}
