<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;

class ContractualRelationshipType extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    public function contractualRelationships()
    {
        return $this->hasMany(ContractualRelationship::class, 'contractual_relationship_type_id');
    }
}
