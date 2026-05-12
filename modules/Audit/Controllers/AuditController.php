<?php

declare(strict_types=1);

namespace Modules\Audit\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Audit\Handlers\DeleteAuditHandler;
use Modules\Audit\Presenters\AuditPresenter;
use Modules\Audit\Requests\DeleteAuditRequest;
use Modules\Audit\Requests\GetAuditListRequest;
use Modules\Audit\Requests\GetAuditRequest;
use Modules\Audit\Services\AuditCRUDService;
use OwenIt\Auditing\Models\Audit;
use Ramsey\Uuid\Uuid;

class AuditController extends Controller
{
    public function __construct(
        private AuditCRUDService   $auditService,
        private DeleteAuditHandler $deleteAuditHandler,
    )
    {
    }

    public function index(GetAuditListRequest $request)
    {

        $list = $this->auditService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );
        return Json::items(AuditPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function activityLog(GetAuditListRequest $request)
    {
        $list = $this->auditService->groupedByDate();

        // Apply presenter to each audit item while maintaining date grouping
        $formattedList = [];
        foreach ($list as $date => $audits) {
            $presented = array_values(array_filter(AuditPresenter::collection($audits)));
            if (!empty($presented)) {
                $formattedList[$date] = $presented;
            }
        }

        return response([
            "code" => "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
            "message" => null,
            "payload" => $formattedList
        ]);
    }

    public function show(GetAuditRequest $request): JsonResponse
    {
        $item = $this->auditService->get(Uuid::fromString($request->route('id')));

        $presenter = new AuditPresenter($item);

        return Json::item($presenter->getData());
    }


    public function delete(DeleteAuditRequest $request): JsonResponse
    {
        $this->deleteAuditHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
