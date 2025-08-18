<?php

declare(strict_types=1);

namespace Modules\Test\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Test\Handlers\DeleteTestHandler;
use Modules\Test\Handlers\UpdateTestHandler;
use Modules\Test\Presenters\TestPresenter;
use Modules\Test\Requests\CreateTestRequest;
use Modules\Test\Requests\DeleteTestRequest;
use Modules\Test\Requests\GetTestListRequest;
use Modules\Test\Requests\GetTestRequest;
use Modules\Test\Requests\UpdateTestRequest;
use Modules\Test\Services\TestCRUDService;
use Modules\Test\Exports\TestExport;
use Modules\Test\Requests\ExportTestRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class TestController extends Controller
{
    public function __construct(
        private TestCRUDService $testService,
        private UpdateTestHandler $updateTestHandler,
        private DeleteTestHandler $deleteTestHandler,
    ) {
    }

    public function index(GetTestListRequest $request): JsonResponse
    {
        $list = $this->testService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TestPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTestRequest $request): JsonResponse
    {
        $item = $this->testService->get(Uuid::fromString($request->route('id')));

        $presenter = new TestPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTestRequest $request): JsonResponse
    {
        $createdItem = $this->testService->create($request->createCreateTestDTO());

        $presenter = new TestPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTestRequest $request): JsonResponse
    {
        $command = $request->createUpdateTestCommand();
        $this->updateTestHandler->handle($command);

        $item = $this->testService->get($command->getId());

        $presenter = new TestPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTestRequest $request): JsonResponse
    {
        $this->deleteTestHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export test to a file
     *
     * @param ExportTestRequest $request
     */
    public function export(ExportTestRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'test.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new TestExport($this->testService, $filters), $fileName);
    }
}
