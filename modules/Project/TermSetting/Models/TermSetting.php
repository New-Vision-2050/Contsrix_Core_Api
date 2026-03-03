<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Project\TermSetting\Database\factories\TermSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\TermServices\Models\TermServices;
use Modules\Project\ProjectType\Models\ProjectType;
use Nevadskiy\Tree\AsTree;

class TermSetting extends Model
{
    use HasFactory;
    use BaseFilterable;
    use AsTree;
    use BelongsToTenant;

    protected $table = "term_settings";

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'project_type_id',
        'company_id',
        'path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'int',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function termServices()
    {
        return $this->belongsToMany(
            TermServices::class,
            'term_setting_term_services',
            'term_setting_id',
            'term_services_id'
        )->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): TermSettingFactory
    {
        return TermSettingFactory::new();
    }
}
