<?php

namespace Modules\Tenant\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Traits\BelongsToTenant;

class Document extends Model
{
    use HasFactory;
    use UuidTrait;
    use BelongsToTenant;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'file_size' => 'integer',
    ];

    /**
     * Get the user that uploaded the document.
     */
    public function uploader()
    {
        return $this->belongsTo(CompanyUser::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the document.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return url('storage/' . $this->file_path);
    }
}