<?php

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\Violation;

class ViolationService
{
    public function listAll(): Collection
    {
        return Violation::orderBy('code')->get();
    }
}
