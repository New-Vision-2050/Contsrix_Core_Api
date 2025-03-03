<?php

declare(strict_types=1);

namespace Modules\Setting\Services;

use Modules\Auth\Repositories\VerficationQuestionRepository;
use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Modules\Setting\DTO\GetUserQuestionsDTO;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use Modules\Setting\Presenters\DriverPresenter;
use Modules\Setting\Presenters\LoginOptionLookupPresenter;
use Modules\Setting\Presenters\LoginOptionPresenter;
use Modules\Setting\Repositories\DriverRepository;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\QuestionSettingRepository;
use Modules\Setting\Repositories\SettingRepository;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\UuidInterface;
use function Laravel\Prompts\password;

class QuestionSettingService
{
    public function __construct(
    private QuestionSettingRepository $questionSettingRepository,
        private VerficationQuestionRepository $verficationQuestionRepository,
        private UserCRUDService $userCRUDService
    )
    {
    }

    public function all()
    {
        return $this->questionSettingRepository->all();
    }

    public function getQuestionUserAnswered (GetUserQuestionsDTO $getUserQuestionsDTO)
    {
        $user = $this->userCRUDService->getUserByIdentifier($getUserQuestionsDTO->getIdentifier());
        return $this->verficationQuestionRepository->findBy(['user_id'=>$user->id]);
    }



}
