<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Attendance\Requests\AttendanceAttachmentRequest;
use Modules\CompanyUser\Handlers\UpdateCompanyUserIdentityDataHandler;
use Modules\CompanyUser\Presenters\CompanyIdentityDataPresenter;
use Modules\CompanyUser\Presenters\CompanyUserImagePresenter;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\CompanyUserIUploadmageService;
use Modules\CompanyUser\Services\IdentityDataService;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class AttendanceAttachmentController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService $companyUserService,
        private CompanyUserIUploadmageService $companyUserIUploadImageService,
        private UpdateCompanyUserIdentityDataHandler $updateCompanyUserIdentityDataHandler,
        private IdentityDataService $identityDataService,
        private UserRepository $userRepository,
    ) {
    }

    public function show(): JsonResponse
    {
        $companyUser = $this->currentCompanyUser();

        return Json::item([
            'profile' => (new CompanyUserImagePresenter($companyUser))->getData(),
            'documents' => (new CompanyIdentityDataPresenter($companyUser))->getData(),
        ]);
    }

    public function store(AttendanceAttachmentRequest $request): JsonResponse
    {
        $user = $this->currentUser();

        if ($request->get('key') === 'profile') {
            $companyUser = $this->companyUserIUploadImageService->uploadFile($request, Uuid::fromString((string) $user->id));

            return Json::item([
                'key' => 'profile',
                'profile' => (new CompanyUserImagePresenter($companyUser))->getData(),
            ], [], 'Photo uploaded successfully');
        }

        $globalCompanyUserId = Uuid::fromString((string) $user->global_company_user_id);

        $command = $request->updateIdentityDataCommand();
        $command->global_id = $globalCompanyUserId;

        $this->updateCompanyUserIdentityDataHandler->handle($command);
        $this->identityDataService->uploadFile($request, $globalCompanyUserId);

        $companyUser = $this->companyUserService->getGlobalId($globalCompanyUserId);

        return Json::item([
            'key' => $request->get('key'),
            'documents' => (new CompanyIdentityDataPresenter($companyUser))->getData(),
        ]);
    }

    private function currentUser()
    {
        return $this->userRepository->getUser(Uuid::fromString((string) auth()->user()->id));
    }

    private function currentCompanyUser()
    {
        $user = $this->currentUser();

        return $this->companyUserService->get(
            Uuid::fromString((string) $user->global_company_user_id),
        );
    }
}
