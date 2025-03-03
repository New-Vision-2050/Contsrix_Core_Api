<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
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
        try {
            DB::beginTransaction();
            $hashedAnswersForUser = collect($data)->map(function ($item) {
                $item["id"] = Uuid::uuid4()->toString();
                $item["user_id"] = auth()->user()->id;
                $item["answer"] = Hash::make($item["answer"]);
                return $item;

            })->toArray();
            $this->model->where("user_id", auth()->user()->id)->delete();
            $verificationQuestion =$this->model->insert($hashedAnswersForUser);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
        }

        return $verificationQuestion;

    }

}
