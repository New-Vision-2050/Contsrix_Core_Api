<?php

declare(strict_types=1);

namespace Modules\Setting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Models\IdentifierSetting;
use Modules\Setting\Models\Setting;
use Ramsey\Uuid\UuidInterface;

class IdentifierSettingRepository extends BaseRepository
{
    public function __construct(IdentifierSetting $model)
    {
        parent::__construct($model);
    }

    public function getIdentifierSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }



    public function makeIdentifierSettingDefault(UuidInterface $id)
    {
        try {
            $identifier = $this->findOneByOrFail(['id' => $id]);

        } catch (\Exception $e) {
            throw new \Exception(__("validation.update-not-successful"), 500);
        }
        if ($this->countBy(["status"=>1]) == 1 && $identifier->status^1 == 0) {
            throw new \Exception(__("validation.deactivate-not-successful-must-have-one"), 500);
        }
        $identifier->update(['status' => $identifier->status^1]);
    }
}
