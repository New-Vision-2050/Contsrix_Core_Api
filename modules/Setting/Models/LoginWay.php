<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class LoginWay extends Model
{
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    public $with = ['loginWaySteps'];

    protected $fillable = [
        "name",
    ];
    protected $casts = [
        'id' => "string",

    ];

    public function loginWaySteps()
    {
        return $this->hasMany(LoginWayStep::class, 'login_way_id', 'id')->orderBy("order", "ASC");
    }

    public function delete()
    {
        try {
            $this->loginWaySteps()->each(function ($step) {
                $step->drivers()->detach();
            });
            $this->loginWaySteps()->delete();
            return parent::delete();
        } catch (\Exception $e) {
            throw new \Exception(__("validation.delete-not-successful"), 500);
        }

    }
}
