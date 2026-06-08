<?php

declare(strict_types=1);

namespace Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Reports\Presenters\ReportListPresenter;
use Modules\Reports\Presenters\ReportPresenter;
use Modules\Reports\Requests\CreateReportRequest;
use Modules\Reports\Requests\DeleteReportRequest;
use Modules\Reports\Requests\GetReportListRequest;
use Modules\Reports\Requests\GetReportRequest;
use Modules\Reports\Services\ReportCRUDService;
use Ramsey\Uuid\Uuid;

class ReportController extends Controller
{
    public function __construct(
        private ReportCRUDService $reportService,
    ) {
    }

    public function list(GetReportListRequest $request): JsonResponse
    {
        $page    = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 10);

        $list   = $this->reportService->list($page, $perPage);
        $offset = ($page - 1) * $perPage;
        $items  = ReportListPresenter::collection($list['data']);

        foreach ($items as $index => &$item) {
            $item['serial_number'] = $offset + $index + 1;
        }
        unset($item);

        return Json::items(
            $items,
            paginationSettings: $list['pagination'],
        );
    }

    public function show(GetReportRequest $request): JsonResponse
    {
        $report = $this->reportService->get(Uuid::fromString((string) $request->route('id')));

        return Json::item((new ReportPresenter($report))->getData());
    }

    public function store(CreateReportRequest $request): JsonResponse
    {
        $report = $this->reportService->create($request->toDTO());

        return Json::item((new ReportPresenter($report))->getData());
    }

    public function regenerate(GetReportRequest $request): JsonResponse
    {
        $report = $this->reportService->regenerate(Uuid::fromString((string) $request->route('id')));

        return Json::item((new ReportPresenter($report))->getData());
    }

    public function delete(DeleteReportRequest $request): JsonResponse
    {
        $this->reportService->delete(Uuid::fromString((string) $request->route('id')));

        return Json::deleted();
    }

    public function download(GetReportRequest $request): Response
    {
        return $this->reportService->download(Uuid::fromString((string) $request->route('id')));
    }
}
