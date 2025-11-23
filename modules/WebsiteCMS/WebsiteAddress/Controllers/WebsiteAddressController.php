<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteAddress\Handlers\DeleteWebsiteAddressHandler;
use Modules\WebsiteCMS\WebsiteAddress\Handlers\UpdateWebsiteAddressHandler;
use Modules\WebsiteCMS\WebsiteAddress\Presenters\WebsiteAddressPresenter;
use Modules\WebsiteCMS\WebsiteAddress\Requests\CreateWebsiteAddressRequest;
use Modules\WebsiteCMS\WebsiteAddress\Requests\DeleteWebsiteAddressRequest;
use Modules\WebsiteCMS\WebsiteAddress\Requests\GetWebsiteAddressListRequest;
use Modules\WebsiteCMS\WebsiteAddress\Requests\GetWebsiteAddressRequest;
use Modules\WebsiteCMS\WebsiteAddress\Requests\UpdateWebsiteAddressRequest;
use Modules\WebsiteCMS\WebsiteAddress\Services\WebsiteAddressCRUDService;
use Modules\WebsiteCMS\WebsiteAddress\Exports\WebsiteAddressExport;
use Modules\WebsiteCMS\WebsiteAddress\Requests\ExportWebsiteAddressRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteAddressController extends Controller
{
    public function __construct(
        private WebsiteAddressCRUDService $websiteAddressService,
        private UpdateWebsiteAddressHandler $updateWebsiteAddressHandler,
        private DeleteWebsiteAddressHandler $deleteWebsiteAddressHandler,
    ) {
    }

    public function index(GetWebsiteAddressListRequest $request): JsonResponse
    {
        $list = $this->websiteAddressService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteAddressPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteAddressRequest $request): JsonResponse
    {
        $item = $this->websiteAddressService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteAddressPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteAddressRequest $request): JsonResponse
    {
        $createdItem = $this->websiteAddressService->create($request->createCreateWebsiteAddressDTO());

        $presenter = new WebsiteAddressPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteAddressRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteAddressCommand();
        $this->updateWebsiteAddressHandler->handle($command);

        $item = $this->websiteAddressService->get($command->getId());

        $presenter = new WebsiteAddressPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteAddressRequest $request): JsonResponse
    {
        $this->deleteWebsiteAddressHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websiteaddress to a file
     *
     * @param ExportWebsiteAddressRequest $request
     */
    public function export(ExportWebsiteAddressRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_address.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new WebsiteAddressExport($this->websiteAddressService, $filters), $fileName);
    }
}
