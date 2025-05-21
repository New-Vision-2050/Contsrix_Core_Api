<?php

declare(strict_types=1);

namespace Modules\SubEntity\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\SubEntity\Services\SubEntityRecordsService;
use Modules\SubEntity\Requests\GetSubEntityRecordsRequest;

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
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        // Create a presenter that takes the sub-entity + records and present the records based on sub-entity attributes
        return Json::items($list['data'] ?? [], $list['pagination']);
    }
}
