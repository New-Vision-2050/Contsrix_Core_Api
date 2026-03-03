<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\TermServiceSetting\Database\factories\TermServiceSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\TermSetting\Models\TermSetting;
use Modules\ClientRequest\Models\ClientRequestService;

class TermServiceSetting extends Model
{
    use HasFactory;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'term_service_settings';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'company_id',
    ];

    protected $casts = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function termSettings()
    {
        return $this->belongsToMany(
            TermSetting::class,
            'term_service_setting_term_setting',
            'term_service_setting_id',
            'term_setting_id'
        )->withTimestamps();
    }

    protected static function newFactory(): TermServiceSettingFactory
    {
        return TermServiceSettingFactory::new();
    }
}
