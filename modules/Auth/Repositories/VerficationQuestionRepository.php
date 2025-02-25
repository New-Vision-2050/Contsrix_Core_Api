<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Auth\Models\VerficationData;
use Modules\Auth\Models\VerificationQuestion;


/**
 * @property VerficationData $model
 */
class VerficationQuestionRepository extends BaseRepository
{
    public function __construct(VerificationQuestion $model)
    {
        parent::__construct($model);
    }

    public function createVerficationQuestion(array $data)
    {
        return $this->model->create($data);
    }

}
