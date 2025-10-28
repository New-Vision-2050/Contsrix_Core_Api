<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\PaymentMethod\DTO\CreatePaymentMethodDTO;
use Modules\Ecommerce\PaymentMethod\Models\PaymentMethod;
use Modules\Ecommerce\PaymentMethod\Repositories\PaymentMethodRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class PaymentMethodCRUDService
{
    use HasExportService;

    public function __construct(
        private PaymentMethodRepository $repository,
    ) {
    }

    public function create(CreatePaymentMethodDTO $createPaymentMethodDTO): PaymentMethod
    {
         return $this->repository->createPaymentMethod($createPaymentMethodDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): PaymentMethod
    {
        return $this->repository->getPaymentMethod(
            id: $id,
        );
    }

    public function getMergedPaymentMethods(): \Illuminate\Support\Collection
    {

        $paymentMethodsData = \Modules\Shared\PaymentMethodData\Models\PaymentMethodData::get();
        

        $existingPaymentMethods = \Modules\Ecommerce\PaymentMethod\Models\PaymentMethod::all()
            ->keyBy('type'); 
        
        return $paymentMethodsData->map(function ($paymentData) use ($existingPaymentMethods) {
            $paymentMethod = $existingPaymentMethods->get($paymentData->type);
            
            return (object) [
                'id' => $paymentData->id,
                'type' => $paymentData->type,
                'name' => $paymentData->name,
                'is_active' => $paymentMethod ? $paymentMethod->is_active : false, 
                'created_at' => $paymentData->created_at,
                'updated_at' => $paymentData->updated_at,
            ];
        });
    }

    public function togglePaymentMethodStatus(string $type): array
    {
        $paymentMethod = \Modules\Ecommerce\PaymentMethod\Models\PaymentMethod::where('type', $type)->first();
        
        if ($paymentMethod) {
            $paymentMethod->is_active = !$paymentMethod->is_active;
            $paymentMethod->save();
            
            $message = $paymentMethod->is_active 
                ? 'تم تفعيل طريقة الدفع بنجاح' 
                : 'تم إلغاء تفعيل طريقة الدفع بنجاح';
        } else {
            $paymentMethod = \Modules\Ecommerce\PaymentMethod\Models\PaymentMethod::create([
                'type' => $type,
                'is_active' => true
            ]);
            
            $message = 'تم إنشاء وتفعيل طريقة الدفع بنجاح';
        }
        
        $paymentMethodData = \Modules\Shared\PaymentMethodData\Models\PaymentMethodData::where('type', $type)->first();
        
        if (!$paymentMethodData) {
            throw new \Exception('طريقة الدفع غير موجودة في البيانات الأساسية');
        }
        
        $responseData = (object) [
            'id' => $paymentMethodData->id,
            'type' => $paymentMethodData->type,
            'name' => $paymentMethodData->name,
            'is_active' => $paymentMethod->is_active,
            'created_at' => $paymentMethodData->created_at,
            'updated_at' => $paymentMethodData->updated_at,
        ];
        
        return [
            'data' => $responseData,
            'message' => $message
        ];
    }
}
