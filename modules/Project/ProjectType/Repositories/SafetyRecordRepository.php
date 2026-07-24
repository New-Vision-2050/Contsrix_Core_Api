<?php

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyRecordRepository extends BaseRepository
{
    public function __construct(SafetyRecord $model)
    {
        parent::__construct($model);
    }
}
