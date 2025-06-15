<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\ProfessionalCertificate\Database\factories\ProfessionalCertificateFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;
<<<<<<< HEAD
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

//use BasePackage\Shared\Traits\HasTranslations;

class ProfessionalCertificate extends Model implements HasMedia
=======

//use BasePackage\Shared\Traits\HasTranslations;

class ProfessionalCertificate extends Model
>>>>>>> 7be6c72c (merge with stage (first version ))
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
<<<<<<< HEAD
    use InteractsWithMedia;
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'professional_bodie_id',
        'accreditation_name',
        'accreditation_number',
        'accreditation_degree',
        'date_obtain',
        'date_end',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): ProfessionalCertificateFactory
    {
        return ProfessionalCertificateFactory::new();
    }

    public function professionalBodie()
    {
        return $this->belongsTo(ProfessionalBodie::class);
    }

}
