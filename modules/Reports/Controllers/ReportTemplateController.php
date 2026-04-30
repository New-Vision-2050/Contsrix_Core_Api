<?php

declare(strict_types=1);

namespace Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Reports\Presenters\ReportPresenter;
use Modules\Reports\Presenters\ReportTemplatePresenter;
use Modules\Reports\Requests\CreateReportTemplateRequest;
use Modules\Reports\Requests\GenerateFromTemplateRequest;
use Modules\Reports\Requests\GetReportTemplateListRequest;
use Modules\Reports\Requests\UpdateReportTemplateRequest;
use Modules\Reports\Services\ReportTemplateCRUDService;
use Ramsey\Uuid\Uuid;

class ReportTemplateController extends Controller
{
    public function __construct(
        private ReportTemplateCRUDService $templateService,
    ) {
    }

    public function list(GetReportTemplateListRequest $request): JsonResponse
    {
        $list = $this->templateService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
        );

        return Json::items(
            ReportTemplatePresenter::collection($list['data']),
            paginationSettings: $list['pagination'],
        );
    }

    public function show($id): JsonResponse
    {
        $template = $this->templateService->get(Uuid::fromString((string) $id));

        return Json::item((new ReportTemplatePresenter($template))->getData());
    }

    public function store(CreateReportTemplateRequest $request): JsonResponse
    {
        $template = $this->templateService->create($request->toDTO());

        return Json::item((new ReportTemplatePresenter($template))->getData());
    }

    public function update(UpdateReportTemplateRequest $request): JsonResponse
    {
        $template = $this->templateService->update($request->toDTO());

        return Json::item((new ReportTemplatePresenter($template))->getData());
    }

    public function delete($id): JsonResponse
    {
        $this->templateService->delete(Uuid::fromString((string) $id));

        return Json::deleted();
    }

    public function generate(GenerateFromTemplateRequest $request, $id): JsonResponse
    {
        $report = $this->templateService->generateReport(
            Uuid::fromString((string) $id),
            $request->validated(),
        );

        return Json::item((new ReportPresenter($report))->getData());
    }
}
