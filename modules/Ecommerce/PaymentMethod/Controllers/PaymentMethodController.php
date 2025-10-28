<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\PaymentMethod\Handlers\DeletePaymentMethodHandler;
use Modules\Ecommerce\PaymentMethod\Handlers\UpdatePaymentMethodHandler;
use Modules\Ecommerce\PaymentMethod\Presenters\PaymentMethodPresenter;
use Modules\Ecommerce\PaymentMethod\Requests\CreatePaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Requests\DeletePaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Requests\GetPaymentMethodListRequest;
use Modules\Ecommerce\PaymentMethod\Requests\GetPaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Requests\UpdatePaymentMethodRequest;
use Modules\Ecommerce\PaymentMethod\Services\PaymentMethodCRUDService;
use Modules\Ecommerce\PaymentMethod\Exports\PaymentMethodExport;
use Modules\Ecommerce\PaymentMethod\Requests\ExportPaymentMethodRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class PaymentMethodController extends Controller
{
    public function __construct(
        private PaymentMethodCRUDService $paymentMethodService,
        private UpdatePaymentMethodHandler $updatePaymentMethodHandler,
        private DeletePaymentMethodHandler $deletePaymentMethodHandler,
    ) {
    }

    public function index(GetPaymentMethodListRequest $request): JsonResponse
    {
        $list = $this->paymentMethodService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(PaymentMethodPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetPaymentMethodRequest $request): JsonResponse
    {
        $item = $this->paymentMethodService->get(Uuid::fromString($request->route('id')));

        $presenter = new PaymentMethodPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreatePaymentMethodRequest $request): JsonResponse
    {
        $createdItem = $this->paymentMethodService->create($request->createCreatePaymentMethodDTO());

        $presenter = new PaymentMethodPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePaymentMethodRequest $request): JsonResponse
    {
        $command = $request->createUpdatePaymentMethodCommand();
        $this->updatePaymentMethodHandler->handle($command);

        $item = $this->paymentMethodService->get($command->getId());

        $presenter = new PaymentMethodPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeletePaymentMethodRequest $request): JsonResponse
    {
        $this->deletePaymentMethodHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export paymentmethod to a file
     *
     * @param ExportPaymentMethodRequest $request
     */
    public function export(ExportPaymentMethodRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'payment_method.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new PaymentMethodExport($this->paymentMethodService, $filters), $fileName);
    }
}
