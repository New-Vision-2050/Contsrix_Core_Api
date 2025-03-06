<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Modules\AdminRequest\Database\factories\AdminRequestFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\User\Models\User;

//use BasePackage\Shared\Traits\HasTranslations;

class AdminRequest extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;

    //use SoftDeletes;

    public array $translatable = ["action"];
    public $with = ["adminRequestTransactions", "user"];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "user_id",
        "request_type",
        "data",
        "status",
        "action",
    ];


    protected $casts = [
        'id' => 'string',
        "data" => "array"
    ];

    public function adminRequestTransactions()
    {
        return $this->hasMany(AdminRequestTransaction::class, 'admin_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): AdminRequestFactory
    {
        return AdminRequestFactory::new();
    }

    public function delete()
    {
        try {
            DB::beginTransaction();
            $this->adminRequestTransactions()->delete();
            parent::delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.delete-not-successful"), 409);
        }

        return true;
    }
}
