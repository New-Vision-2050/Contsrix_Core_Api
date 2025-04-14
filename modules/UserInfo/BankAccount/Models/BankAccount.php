<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\BankAccount\Database\factories\BankAccountFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Country\Models\Country;
use Modules\Shared\Bank\Models\Bank;
use Modules\Shared\Currency\Models\Currency;

//use BasePackage\Shared\Traits\HasTranslations;

class BankAccount extends Model
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
        'user_id',
        'country_id',
        'bank_id',
        'currency_id',
        'user_name',
        'account_number',
        'iban',
        'swift_bic',
        'type'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
