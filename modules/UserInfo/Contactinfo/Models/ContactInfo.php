<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\BankAccount\Database\factories\BankAccountFactory;
use BasePackage\Shared\Traits\BaseFilterable;

//use BasePackage\Shared\Traits\HasTranslations;

class ContactInfo extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'email',
        'other_phone',
        'code_other_phone',
        'phone',
        'phone_code',
        'landline_number',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }
}
