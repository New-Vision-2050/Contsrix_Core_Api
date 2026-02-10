<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;

class ContractualRelationship extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'contractual_relationship_type_id',
        'employment_name',
        'registration_number',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function contractualRelationshipType()
    {
        return $this->belongsTo(ContractualRelationshipType::class, 'contractual_relationship_type_id');
    }
}
