<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Models\VerificationQuestion;
use Modules\Auth\Presenters\VerficationQuestionPresenter;
use Modules\Setting\Handlers\DeleteSettingHandler;
use Modules\Setting\Presenters\SettingPresenter;

use Modules\Setting\Requests\question\GetQuestionAnswerdForUserRequest;
use Modules\Setting\Requests\question\GetQuestionListRequest;
use Modules\Setting\Services\QuestionSettingService;
use Modules\Setting\Services\SettingCRUDService;
use Ramsey\Uuid\Uuid;

class QuestionSettingController extends Controller
{
    public function __construct(
        private QuestionSettingService $questionSettingService
    ) {}

    public function index(GetQuestionListRequest $request): JsonResponse
    {
        return $this->questionSettingService->all();
    }

    public function getUserQuestions(GetQuestionAnswerdForUserRequest $request)
    {
        $verficationQuestion = $this->questionSettingService->getQuestionUserAnswered($request->createGetUserQuestionDTO());
        return Json::item(VerficationQuestionPresenter::collection($verficationQuestion));

    }
}
