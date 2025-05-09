<?php

declare(strict_types=1);

namespace Modules\SubEntity\Models;

use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SubEntity\Database\factories\SubEntityFactory;

class SubEntity extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'icon',
        'is_active',
        'main_program_id',
        'default_attributes',
        'optional_attributes',
        'is_registrable',
        'super_entity',
        'company_id'
    ];

    protected $casts = [
        'id' => 'string',
        'default_attributes' => 'json',
        'optional_attributes' => 'json',
    ];

    public function mainProgram()
    {
        return $this->belongsTo(Program::class, 'main_program_id');
    }

    protected static function newFactory(): SubEntityFactory
    {
        return SubEntityFactory::new();
    }

    public function getAttributesCountAttribute(): int
    {
        $default = is_array($this->default_attributes) ? count($this->default_attributes) : 0;
        $optional = is_array($this->optional_attributes) ? count($this->optional_attributes) : 0;

        return $default + $optional;
    }
}
