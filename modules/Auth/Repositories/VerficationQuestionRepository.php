<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\VerficationData;
use Modules\Auth\Models\VerificationQuestion;
use Ramsey\Uuid\Uuid;


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
        $hashedAnswersForUser = collect($data)->map(function ($item) {
            $item["id"] = Uuid::uuid4()->toString();
            $item["user_id"] = auth()->user()->id;
            $item["answer"] = Hash::make($item["answer"]);
            return $item;

        })->toArray();

        return $this->model->insert($hashedAnswersForUser);
    }

}
