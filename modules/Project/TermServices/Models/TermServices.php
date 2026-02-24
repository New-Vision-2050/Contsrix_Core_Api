<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Project\TermServices\Database\factories\TermServicesFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\TermSetting\Models\TermSetting;

class TermServices extends Model
{
    use HasFactory;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = "term_services";

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'company_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'int',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function termSettings()
    {
        return $this->belongsToMany(
            TermSetting::class,
            'term_setting_term_services',
            'term_services_id',
            'term_setting_id'
        )->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): TermServicesFactory
    {
        return TermServicesFactory::new();
    }
}
