<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Modules\Shared\Media\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyCore\Handlers\DeleteCompanyHandler;
use Modules\Company\CompanyCore\Handlers\UpdateCompanyHandler;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Requests\CreateCompanyRequest;
use Modules\Company\CompanyCore\Requests\DeleteCompanyRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyListRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyRequest;
use Modules\Company\CompanyCore\Requests\UpdateCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyValidateService;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Modules\Company\CompanyCore\Handlers\ActivateCompanyHandler;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;
use Modules\Company\CompanyCore\Requests\ActiveCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyWidgetService;
use App\Services\TransformImgsService;
use Illuminate\Support\Facades\Storage;
class CompanyController extends Controller
{
    public function __construct(
        private CompanyCRUDService $companyService,
        private UpdateCompanyHandler $updateCompanyHandler,
        private DeleteCompanyHandler $deleteCompanyHandler,
        private CompanyValidateService $validateCompanyService,
        private CompanyWidgetService $companyWidgetService,
        private ActivateCompanyHandler $activateCompanyCommand,
        private FileUploadService  $fileUploadService,
        private TransformImgsService  $transformImgsService
    ) {
    }

    public function index(GetCompanyListRequest $request)//: JsonResponse
    {

        $list = $this->companyService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CompanyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetCompanyRequest $request): JsonResponse
    {
        $item = $this->companyService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateCompanyRequest $request): JsonResponse
    {
        $createdItem = $this->companyService->create($request->createCreateCompanyDTO());

        $presenter = new CompanyPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCompanyRequest $request)//: JsonResponse
    {

        $command = $request->createUpdateCompanyCommand();
        $this->updateCompanyHandler->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteCompanyRequest $request): JsonResponse
    {
        $this->deleteCompanyHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
    public function validate(Request $request)//: JsonResponse
    {
        $validationResult = $this->validateCompanyService->validate($request);

        return response()->json([
            'status' => 'success',
            'data' => $validationResult,
        ]);
    }
    public function widget(): JsonResponse
    {
        $total = $this->companyWidgetService->total(); //make a qury in this point and appaly filter //concarncy in laravel
        $active = $this->companyWidgetService->active();
        $completeData = $this->companyWidgetService->completeData();
        $dataActivate = $this->companyWidgetService->dataActivate();

        $totalCalculate = $this->companyWidgetService->totalCalculate();
        $activeCalculate = $this->companyWidgetService->activeCalculate();
        $completeDataCalculate = $this->companyWidgetService->completeDataCalculate();
        $dataActivateCalculate = $this->companyWidgetService->dataActivateCalculate();

        $presenter = new CompanyWidgetPresenter($total,$active,$completeData,$dataActivate,$totalCalculate,$activeCalculate,$completeDataCalculate,$dataActivateCalculate);

        return Json::item($presenter->getData());
    }
    public function activate(ActiveCompanyRequest $request): JsonResponse
    {
        $command = $request->createActiveCompanyCommand();
        $this->activateCompanyCommand->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }
    public function test(Request $request)
    {
        $company = Company::first();

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $service = new FileUploadService();
        $response = $service->uploadFile($company, $request->file('file'),'image/test' ,'company_images', 'public');


        return response()->json($response);

        // Upload as a private file (expires in 10 minutes)
        // $response = $service->uploadToS3($request, 'company_images', 'company_collection', 'private', 10);

    }
    // public function getImage($id)
    // {
    //     $file = Company::findOrFail($id);

    //     $url = Storage::disk('s3')->temporaryUrl($file->image_path, now()->addMinutes(10));

    //     $transformedImage = $this->transformImgsService->optimiseImage($url);

    //     return response()->json([
    //         'message' => 'File retrieved successfully!',
    //         'url' => $url,
    //         'transformed_image' => base64_encode($transformedImage),
    //     ]);
    // }

    public function getImage()
    {
        // Get the first company
        $company = Company::with('media')->first();

        return $company;


        return response()->json(['error' => 'No media found.'], 404);
    }
}
