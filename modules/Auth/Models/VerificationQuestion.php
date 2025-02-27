<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Setting\Models\QuestionSetting;

// use BasePackage\Shared\Traits\HasTranslations;

class VerificationQuestion extends Model
{
    use UuidTrait;
    use BaseFilterable;
    public $with = ['question'];
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'question_id',
        'answer',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function question()
    {
        return $this->belongsTo(QuestionSetting::class, 'question_id');
    }
}
