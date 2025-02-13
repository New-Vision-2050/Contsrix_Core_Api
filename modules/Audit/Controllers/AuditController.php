<?php

declare(strict_types=1);

namespace Modules\Audit\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Audit\Handlers\DeleteAuditHandler;
use Modules\Audit\Handlers\UpdateAuditHandler;
use Modules\Audit\Presenters\AuditPresenter;
use Modules\Audit\Requests\CreateAuditRequest;
use Modules\Audit\Requests\DeleteAuditRequest;
use Modules\Audit\Requests\GetAuditListRequest;
use Modules\Audit\Requests\GetAuditRequest;
use Modules\Audit\Requests\UpdateAuditRequest;
use Modules\Audit\Services\AuditCRUDService;
use Ramsey\Uuid\Uuid;

class AuditController extends Controller
{
    public function __construct(
        private AuditCRUDService $auditService,
        private UpdateAuditHandler $updateAuditHandler,
        private DeleteAuditHandler $deleteAuditHandler,
    ) {
    }

    public function index(GetAuditListRequest $request): JsonResponse
    {
        $list = $this->auditService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['audits' => AuditPresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetAuditRequest $request): JsonResponse
    {
        $item = $this->auditService->get(Uuid::fromString($request->route('id')));

        $presenter = new AuditPresenter($item);

        return Json::buildItems('audit', $presenter->getData());
    }



    public function delete(DeleteAuditRequest $request): JsonResponse
    {
        $this->deleteAuditHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
