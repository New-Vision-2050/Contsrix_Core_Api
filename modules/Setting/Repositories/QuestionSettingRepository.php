<?php

declare(strict_types=1);

namespace Modules\Setting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Setting\Models\QuestionSetting;
use Modules\Setting\Models\Setting;

class QuestionSettingRepository extends BaseRepository
{
    public function __construct(QuestionSetting $model)
    {
        parent::__construct($model);
    }

    public function getSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }


}
