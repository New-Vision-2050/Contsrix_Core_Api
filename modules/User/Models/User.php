<?php

declare(strict_types=1);

namespace Modules\User\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\User\Database\factories\UserFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Tymon\JWTAuth\Contracts\JWTSubject;


//use BasePackage\Shared\Traits\HasTranslations;

class User  extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use Notifiable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];
protected $primaryKey="id";
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $casts = [
        'id' => 'string',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
