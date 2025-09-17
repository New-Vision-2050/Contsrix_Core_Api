<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Payment\Handlers\DeletePaymentHandler;
use Modules\Shared\Payment\Handlers\UpdatePaymentHandler;
use Modules\Shared\Payment\Presenters\PaymentPresenter;
use Modules\Shared\Payment\Requests\CreatePaymentRequest;
use Modules\Shared\Payment\Requests\DeletePaymentRequest;
use Modules\Shared\Payment\Requests\GetPaymentListRequest;
use Modules\Shared\Payment\Requests\GetPaymentRequest;
use Modules\Shared\Payment\Requests\UpdatePaymentRequest;
use Modules\Shared\Payment\Services\PaymentCRUDService;
use Modules\Shared\Payment\Exports\PaymentExport;
use Modules\Shared\Payment\Requests\ExportPaymentRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentCRUDService $paymentService,
        private UpdatePaymentHandler $updatePaymentHandler,
        private DeletePaymentHandler $deletePaymentHandler,
    ) {
    }

    public function index(GetPaymentListRequest $request): JsonResponse
    {
        $list = $this->paymentService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(PaymentPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetPaymentRequest $request): JsonResponse
    {
        $item = $this->paymentService->get(Uuid::fromString($request->route('id')));

        $presenter = new PaymentPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreatePaymentRequest $request): JsonResponse
    {
        $createdItem = $this->paymentService->create($request->createCreatePaymentDTO());

        $presenter = new PaymentPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePaymentRequest $request): JsonResponse
    {
        $command = $request->createUpdatePaymentCommand();
        $this->updatePaymentHandler->handle($command);

        $item = $this->paymentService->get($command->getId());

        $presenter = new PaymentPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeletePaymentRequest $request): JsonResponse
    {
        $this->deletePaymentHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export payment to a file
     *
     * @param ExportPaymentRequest $request
     */
    public function export(ExportPaymentRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'payment.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new PaymentExport($this->paymentService, $filters), $fileName);
    }
}
