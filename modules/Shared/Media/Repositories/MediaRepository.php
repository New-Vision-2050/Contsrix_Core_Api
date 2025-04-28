<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Ramsey\Uuid\UuidInterface;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property Media $model
 * @method CompanyUser findOneOrFail($id)
 * @method CompanyUser findOneByOrFail(array $data)
 */
class MediaRepository extends BaseRepository
{
    public function __construct(Media $model)
    {
        parent::__construct($model);
    }

    public function delete($ids)
    {
        return $this->model->whereIn('id',$ids)->delete();
    }

}
