<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoProduct\DTO\CreateEcoProductDTO;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;

class EcoProductCRUDService
{
    public function __construct(
        private EcoProductRepository $repository,
        private FileUploadService $fileUploadService,
    ) {
    }

    public function create(CreateEcoProductDTO $createEcoProductDTO): EcoProduct
    {

        $createEcoProduct = $this->repository->createEcoProduct($createEcoProductDTO->toArray());

        $mainImageFile = $createEcoProductDTO->mainImage;
        $otherImageFiles = $createEcoProductDTO->otherImages;

        if ($mainImageFile->isFile()) {
            $createEcoProduct->clearMediaCollection('eco_product_main_image');
            $companyName =  $createEcoProduct->company->name ?? 'UnknownCompany';
            $path = $companyName . '/ecommerce/' . $createEcoProduct->name ;

            $this->fileUploadService->uploadFile(
                $createEcoProduct,
                $mainImageFile,
                $path,
                'eco_product_main_image',
                "public"
            );
        }

        if ($otherImageFiles) {
            $this->fileUploadService->uploadFile(
                $createEcoProduct,
                $otherImageFiles,
                $path,
                'eco_product_other_image',
                "public"
            );
        }

        return $createEcoProduct;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoProduct
    {
        return $this->repository->getEcoProduct(
            id: $id,
        );
    }
}
