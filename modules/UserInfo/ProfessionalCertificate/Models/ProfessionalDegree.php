<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalDegree extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function professionalCertificates()
    {
        return $this->hasMany(ProfessionalCertificate::class);
    }
}
