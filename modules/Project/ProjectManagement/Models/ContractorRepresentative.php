<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorRepresentative extends Model
{
    use UuidTrait;

    protected $table = 'contractor_representatives';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'contractor_id',
        'name',
        'mobile',
        'nationality',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }
}
