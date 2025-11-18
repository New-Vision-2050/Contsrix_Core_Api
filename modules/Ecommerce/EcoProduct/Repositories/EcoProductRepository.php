<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Ecommerce\EcoProduct\Commands\Dashboard\UpdateEcoProductDashboardCommand;
use Modules\Ecommerce\EcoProduct\Commands\UpdateEcoProductCommand;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Shared\Media\Services\FileUploadService;

/**
 * @property EcoProduct $model
 * @method EcoProduct findOneOrFail($id)
 * @method EcoProduct findOneByOrFail(array $data)
 */
class EcoProductRepository extends BaseRepository
{
    public function __construct(
        EcoProduct $model,
        private FileUploadService $fileUploadService,
    )
    {
        parent::__construct($model);
    }

    public function getEcoProductList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoProduct(UuidInterface $id): EcoProduct
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

   public function createEcoProduct(array $data): EcoProduct
    {
        $details = $data['details'] ?? null;
        $customFields = $data['custom_fields'] ?? null;

        $seo = $data['seo'] ?? null;
        $associatedProductIds = $data['associated_product_ids'] ?? [];
        $taxes = $data['taxes'] ?? [];

        unset($data['details'], $data['custom_fields'], $data['seo'], $data['associated_product_ids'], $data['taxes']);

        $ecoProduct =  $this->model::create($data);

        if ($details) {

            $ecoProduct->details()->createMany($details);
        }
        if ($customFields) {
            $ecoProduct->customFields()->createMany($customFields);
        }
        if ($seo) {
            $ecoProduct->seo()->create($seo);
        }

        if (!empty($associatedProductIds)) {
            $ecoProduct->associatedProducts()->attach($associatedProductIds);
        }

        if ($taxes) {
            $taxes = array_map(function ($tax) use ($ecoProduct) {
                $tax['company_id'] = $ecoProduct->company_id;
                return $tax;
            }, $taxes);

            $ecoProduct->taxes()->createMany($taxes);
        }
        return $ecoProduct;
    }

  public function updateEcoProduct(UuidInterface $id, UpdateEcoProductDashboardCommand $command): EcoProduct
    {
        $ecoProduct = $this->findOneOrFail($id);
        $productData = $command->toArray();

        $mainImageFile = $command->getMainImage();
        $otherImageFiles = $command->getOtherImages();
        $otherImagesToDelete = $command->getOtherImagesToDelete();

        // Update the main EcoProduct record
        $ecoProduct->update($productData);

        if ($command->getTaxes() !== null) { // Check if 'taxes' field was provided in the update request
            $ecoProduct->taxes()->delete(); // Delete all old taxes associated with this product
            if (!empty($command->getTaxes())) {
                $taxesToCreate = array_map(function($tax) use ($ecoProduct) {
                    $tax['company_id'] = $ecoProduct->company_id; // Ensure company_id is set for new taxes
                    return $tax;
                }, $command->getTaxes());
                $ecoProduct->taxes()->createMany($taxesToCreate); // Create new taxes
            }
        }

        // Details: Similar to taxes, replacing all if provided.
        if ($command->getDetails() !== null) {
            $ecoProduct->details()->delete();
            if (!empty($command->getDetails())) {
                $ecoProduct->details()->createMany($command->getDetails());
            }
        }

        // Custom Fields: Similar to taxes, replacing all if provided.
        if ($command->getCustomFields() !== null) {
            $ecoProduct->customFields()->delete();
            if (!empty($command->getCustomFields())) {
                $ecoProduct->customFields()->createMany($command->getCustomFields());
            }
        }

        if ($command->getSeo() !== null) {
            $seoData = $command->getSeo();
            if (!empty($seoData)) {
                $ecoProduct->seo()->updateOrCreate(
                    ['product_id' => $ecoProduct->id],
                    $seoData
                );
            } else {
                $ecoProduct->seo()->delete();
            }
        }

        if ($command->getAssociatedProductIds() !== null) {
            $ecoProduct->associatedProducts()->sync($command->getAssociatedProductIds());
        }

        $companyName =  $ecoProduct->company->name ?? 'UnknownCompany';
        $path = $companyName . '/ecommerce/' . $ecoProduct->name ;

        if ($otherImageFiles) {
            $this->fileUploadService->uploadFile(
                $ecoProduct,
                $otherImageFiles,
                $path,
                'eco_product_other_image',
                "public"
            );
        }

        if (!empty($otherImagesToDelete)) {
            foreach ($otherImagesToDelete as $mediaIdToDelete) {
                $mediaItem = $ecoProduct->getMedia('eco_product_other_image')->find($mediaIdToDelete);
                if ($mediaItem) {
                    $mediaItem->delete();
                }
            }
        }

        if ($mainImageFile) {
            $ecoProduct->clearMediaCollection('eco_product_main_image');
            $this->fileUploadService->uploadFile(
                $ecoProduct,
                $mainImageFile,
                $path,
                'eco_product_main_image',
                "public"
            );
        }elseif ($command->getDeleteMainImage() === true) { // Case 2: Explicitly asked to delete main image
            $ecoProduct->clearMediaCollection('eco_product_main_image');
        }
        
        return $ecoProduct->load(['taxes', 'details', 'customFields', 'seo', 'associatedProducts']);
    }

    public function deleteEcoProduct(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function updateDiscountProduct(UuidInterface $id,array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Get paginated products with filter support
     */
    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc',
        array $relations = [],
    ): array {
        // Use filter if model has scopeFilter method
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->where($conditions);
        } else {
            $query = $this->model->where($conditions);
        }

        if (!empty($relations)) {
            $query->with($relations);
        }

        $count = $query->count();

        $paginatedData = $query
            ->orderBy($orderBy, $sortBy)
            ->forPage($page, $perPage)
            ->get();

        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    /**
     * Get visible product by ID with optional relations
     */
    public function getVisibleEcoProduct(UuidInterface $id, array $relations = []): EcoProduct
    {
        $product = $this->findOneByOrFail([
            'id' => $id->toString(),
            'is_visible' => true,
        ]);

        if (!empty($relations)) {
            $product->load($relations);
        }

        return $product;
    }
}
