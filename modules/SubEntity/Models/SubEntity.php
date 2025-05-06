<?php

declare(strict_types=1);

namespace Modules\SubEntity\Models;

use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SubEntity\Database\factories\SubEntityFactory;

class SubEntity extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
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
        'super_entity'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function mainProgram()
    {
        return $this->belongsTo(Program::class, 'main_program_id');
    }

    protected static function newFactory(): SubEntityFactory
    {
        return SubEntityFactory::new();
    }
}
