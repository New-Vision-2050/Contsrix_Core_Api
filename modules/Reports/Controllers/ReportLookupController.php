<?php

declare(strict_types=1);

namespace Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Reports\Presenters\ReportLookupPresenter;
use Modules\Reports\Services\ReportLookupService;

class ReportLookupController extends Controller
{
    public function __construct(
        private ReportLookupService $lookupService,
    ) {
    }

    public function index(): JsonResponse
    {
        $presenter = new ReportLookupPresenter($this->lookupService->all());

        return Json::item($presenter->getData());
    }
}
