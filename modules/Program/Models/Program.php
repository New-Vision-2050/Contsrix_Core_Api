<?php

declare(strict_types=1);

namespace Modules\Program\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Program\Database\factories\ProgramFactory;
use Spatie\LaravelPackageTools\Concerns\Package\HasTranslations as PackageHasTranslations;

class Program extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use PackageHasTranslations;
    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'is_active',
        'parent_id'
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'json'
    ];

    protected static function booted(): void
    {
        static::creating(function (self $program) {
            if (isset($program->name['en'])) {
                $program->slug = Str::slug($program->name['en']);
            }
        });

        static::updating(function (self $program) {
            if ($program->isDirty('name') && isset($program->name['en'])) {
                $program->slug = Str::slug($program->name['en']);
            }
        });
    }


    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }
}
