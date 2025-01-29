<?php

namespace App\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use UuidTrait;

    protected $primaryKey="id";
    protected $fillable=["key", "value"];
    public $incrementing = false;

    protected $casts =[
        'id' => UuidCast::class,
    ];

}
