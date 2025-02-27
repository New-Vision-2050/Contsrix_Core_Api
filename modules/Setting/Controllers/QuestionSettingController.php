<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Handlers\DeleteSettingHandler;
use Modules\Setting\Handlers\UpdateSettingHandler;
use Modules\Setting\Presenters\SettingPresenter;
use Modules\Setting\Requests\Controllers\CreateSettingRequest;
use Modules\Setting\Requests\Controllers\DeleteSettingRequest;
use Modules\Setting\Requests\Controllers\GetSettingListRequest;
use Modules\Setting\Requests\Controllers\GetSettingRequest;
use Modules\Setting\Requests\Controllers\UpdateSettingRequest;
use Modules\Setting\Requests\question\GetQuestionListRequest;
use Modules\Setting\Services\QuestionSettingService;
use Modules\Setting\Services\SettingCRUDService;
use Ramsey\Uuid\Uuid;

class QuestionSettingController extends Controller
{
    public function __construct(
        public QuestionSettingService $questionSettingService

    )
    {
    }

    public function index(GetQuestionListRequest $request): JsonResponse
    {
        return $this->questionSettingService->all();
    }

    public function getQuestionsUserAnswered(): JsonResponse
    {
        //TODO: get all questions user answered
    }


}
