<?php

declare(strict_types=1);

namespace Modules\SubEntity\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\SubEntity\Presenters\RegistrationFormPresenter;
use Modules\SubEntity\Services\RegistrationFormCRUDService;

class RegistrationFormController extends Controller
{
    public function __construct(
        private RegistrationFormCRUDService $registrationFormCRUDService,
    ) {
    }

    public function getRegistrationForms(): JsonResponse
    {
        $forms = $this->registrationFormCRUDService->getRegistrationFormSelectionList();

        return Json::items(RegistrationFormPresenter::collection($forms));
    }
}
