<?php

declare(strict_types=1);

namespace Modules\SubEntity\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\SubEntity\Services\SubEntityRecordsService;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\SubEntity\Requests\GetSubEntityRecordsRequest;
use Modules\SubEntity\Requests\ExportSubEntityRecordsRequest;
use Modules\SubEntity\Exports\SubEntityRecordsExport;
use Maatwebsite\Excel\Facades\Excel;

class SubEntityRecordsController extends Controller
{
    public function __construct(
        private SubEntityRecordsService $subEntityRecordsService,
    ) {
    }

    public function index(GetSubEntityRecordsRequest $request): JsonResponse
    {
        $list = $this->subEntityRecordsService->getRecords(
            $request->get('sub_entity_id'),
            $request->get('registration_form_id'),
            $request->get('branch_id'),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CompanyUserPresenter::collection($list["data"] ?? []),paginationSettings: $list['pagination'] ?? []);
    }

    public function widgets(GetSubEntityRecordsRequest $request): JsonResponse
    {
        $widgetsData = $this->subEntityRecordsService->getWidgetsData(
            $request->get('sub_entity_id'),
            $request->get('registration_form_id')
        );

        return Json::item($widgetsData, message: 'Sub entity records widgets retrieved successfully');
    }

    /**
     * Export sub entity records to a file
     *
     * @param ExportSubEntityRecordsRequest $request
     */
    public function export(ExportSubEntityRecordsRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'sub_entity_records.' . $format;

        $filters = $request->getFilters();

        return Excel::download(new SubEntityRecordsExport($this->subEntityRecordsService, $filters), $fileName);
    }
}
