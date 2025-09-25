<?php

declare(strict_types=1);

namespace Modules\DocumentType\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DocumentType\Handlers\DeleteDocumentTypeHandler;
use Modules\DocumentType\Handlers\UpdateDocumentTypeHandler;
use Modules\DocumentType\Presenters\DocumentTypePresenter;
use Modules\DocumentType\Requests\CreateDocumentTypeRequest;
use Modules\DocumentType\Requests\DeleteDocumentTypeRequest;
use Modules\DocumentType\Requests\GetDocumentTypeListRequest;
use Modules\DocumentType\Requests\GetDocumentTypeRequest;
use Modules\DocumentType\Requests\UpdateDocumentTypeRequest;
use Modules\DocumentType\Services\DocumentTypeCRUDService;
use Modules\DocumentType\Exports\DocumentTypeExport;
use Modules\DocumentType\Requests\ExportDocumentTypeRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class DocumentTypeController extends Controller
{
    public function __construct(
        private DocumentTypeCRUDService $documentTypeService,
        private UpdateDocumentTypeHandler $updateDocumentTypeHandler,
        private DeleteDocumentTypeHandler $deleteDocumentTypeHandler,
    ) {
    }

    public function index(GetDocumentTypeListRequest $request): JsonResponse
    {
        $list = $this->documentTypeService->list(
            $request->getFilters(),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(DocumentTypePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetDocumentTypeRequest $request): JsonResponse
    {
        $item = $this->documentTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new DocumentTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateDocumentTypeRequest $request): JsonResponse
    {
        $createdItem = $this->documentTypeService->create($request->createCreateDocumentTypeDTO());

        $presenter = new DocumentTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateDocumentTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateDocumentTypeCommand();
        $this->updateDocumentTypeHandler->handle($command);

        $item = $this->documentTypeService->get($command->getId());

        $presenter = new DocumentTypePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteDocumentTypeRequest $request): JsonResponse
    {
        $this->deleteDocumentTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export documenttype to a file
     *
     * @param ExportDocumentTypeRequest $request
     */
    public function export(ExportDocumentTypeRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'document_type.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new DocumentTypeExport($this->documentTypeService, $filters), $fileName);
    }
}
