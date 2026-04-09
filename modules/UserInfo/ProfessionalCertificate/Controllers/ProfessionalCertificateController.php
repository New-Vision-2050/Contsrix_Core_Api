<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\ProfessionalCertificate\Handlers\DeleteProfessionalCertificateHandler;
use Modules\UserInfo\ProfessionalCertificate\Handlers\UpdateProfessionalCertificateHandler;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalDegree;
use Modules\UserInfo\ProfessionalCertificate\Presenters\ProfessionalCertificatePresenter;
use Modules\UserInfo\ProfessionalCertificate\Presenters\ProfessionalDegreePresenter;
use Modules\UserInfo\ProfessionalCertificate\Requests\CreateProfessionalCertificateRequest;
use Modules\UserInfo\ProfessionalCertificate\Requests\DeleteProfessionalCertificateRequest;
use Modules\UserInfo\ProfessionalCertificate\Requests\GetProfessionalCertificateListRequest;
use Modules\UserInfo\ProfessionalCertificate\Requests\GetProfessionalCertificateRequest;
use Modules\UserInfo\ProfessionalCertificate\Requests\UpdateProfessionalCertificateRequest;
use Modules\UserInfo\ProfessionalCertificate\Services\ProfessionalCertificateCRUDService;
use Ramsey\Uuid\Uuid;

class ProfessionalCertificateController extends Controller
{
    public function __construct(
        private ProfessionalCertificateCRUDService $professionalCertificateService,
        private UpdateProfessionalCertificateHandler $updateProfessionalCertificateHandler,
        private DeleteProfessionalCertificateHandler $deleteProfessionalCertificateHandler,
        private UserRepository $userRepository
    ) {
    }

    public function index(GetProfessionalCertificateListRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $list = $this->professionalCertificateService->list(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ProfessionalCertificatePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetProfessionalCertificateRequest $request): JsonResponse
    {
        $item = $this->professionalCertificateService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProfessionalCertificatePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProfessionalCertificateRequest $request): JsonResponse
    {
        $createCreateProfessionalCertificateDTO = $request->createCreateProfessionalCertificateDTO();
        $userId = Uuid::fromString($request->input('user_id'));

        $user = $this->userRepository->getUser($userId);
        $createCreateProfessionalCertificateDTO->company_id = $user->company_id;
        $createCreateProfessionalCertificateDTO->global_id = $user->global_company_user_id;

        $createdItem = $this->professionalCertificateService->create($createCreateProfessionalCertificateDTO);

        $presenter = new ProfessionalCertificatePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProfessionalCertificateRequest $request): JsonResponse
    {
        $command = $request->createUpdateProfessionalCertificateCommand();
        $this->updateProfessionalCertificateHandler->handle($command);

        $item = $this->professionalCertificateService->get($command->getId());

        $presenter = new ProfessionalCertificatePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteProfessionalCertificateRequest $request): JsonResponse
    {
        $this->deleteProfessionalCertificateHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function getProfessionalDegrees(): JsonResponse
    {
        $degrees = ProfessionalDegree::where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        return Json::items(ProfessionalDegreePresenter::collection($degrees));
    }
}
