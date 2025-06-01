<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Modules\AdminRequest\Database\factories\AdminRequestFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\AdminRequest\Enum\AdminRequestStatus;
use Modules\AdminRequest\Services\AdminRequestCRUDService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

//use BasePackage\Shared\Traits\HasTranslations;

class AdminRequest extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use CustomBelongsToTenant;
    use InteractsWithMedia;

    //use SoftDeletes;

    public array $translatable = ["action"];
    public $with = ["adminRequestTransactions", "user", "requestable"];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "user_id",
        "request_type",
        "data",
        "status",
        "action",
        "requestable_id",
        "requestable_type",
        "notes"
    ];


    protected $casts = [
        'id' => 'string',
        "data" => "array"
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->serial_number)) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();

                $model->serial_number = app(AdminRequestCRUDService::class)->generateSerialNumber();
            }
        });
    }

    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function adminRequestTransactions()
    {
        return $this->hasMany(AdminRequestTransaction::class, 'admin_request_id');
    }

    public function requestable()
    {
        return $this->morphTo();
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
