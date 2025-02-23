<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Faker\Core\Uuid;
use Ichtrojan\Otp\Models\Otp;
use Modules\Auth\Models\VerficationData;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

/**
 * @property VerficationData $model
 */
class VerficationDataRepository extends BaseRepository
{
    public function __construct(VerficationData $model,private UserRepository $userRepository)
    {
        parent::__construct($model);
    }

    public function createToken(UuidInterface $userId,array $data): ?VerficationData
    {
        $user = $this->userRepository->getUser($userId);

        return $this->updateOrCreate(["user_id"=>$userId], ["user_id"=>$userId,"token"=> hash('sha256', time() . str()->random(8) .$user->email ),"data"=>$data,"expires_at"=>Carbon::now()->addMinutes(5)]);
    }
}
