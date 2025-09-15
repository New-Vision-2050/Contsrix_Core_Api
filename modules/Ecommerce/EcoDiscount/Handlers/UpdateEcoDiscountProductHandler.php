<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Handlers;

use Modules\Ecommerce\EcoDiscount\Commands\UpdateEcoDiscountProductCommand;
use Modules\Ecommerce\EcoDiscount\Repositories\EcoDiscountRepository;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;
use Carbon\Carbon;

class UpdateEcoDiscountProductHandler
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function handle(UpdateEcoDiscountProductCommand $updateEcoDiscountProductCommand)
    {
        // Get the product to access its price for calculations
        $product = $this->repository->getEcoProduct($updateEcoDiscountProductCommand->getId());
        $productPrice = (float) $product->price;

        // Prepare data array with calculations
        $data = [];

        // Handle has_discount
        if ($updateEcoDiscountProductCommand->getHasDiscount() !== null) {
            $data['has_discount'] = $updateEcoDiscountProductCommand->getHasDiscount();
        }

        // Calculate discount amount if percentage is provided but amount is not
        $discountPercentage = $updateEcoDiscountProductCommand->getDiscountPercentage();
        $discountAmount = $updateEcoDiscountProductCommand->getDiscountAmount();

        if ($discountPercentage !== null && $discountAmount === null && $productPrice > 0) {
            $discountAmount = ($productPrice * $discountPercentage) / 100;
        }

        // Add discount fields to data
        if ($discountAmount !== null) {
            $data['discount_amount'] = $discountAmount;
        }

        if ($discountPercentage !== null) {
            $data['discount_percentage'] = $discountPercentage;
        }

        // Set default dates if not provided
        $discountStartDate = $updateEcoDiscountProductCommand->getDiscountStartDate();
        if ($discountStartDate === null && $discountPercentage !== null) {
            $discountStartDate = Carbon::now()->format('Y-m-d');
        }

        if ($discountStartDate !== null) {
            $data['discount_start_date'] = $discountStartDate;
        }

        // Set max discount amount to calculated amount if not provided
        $maxDiscountAmount = $updateEcoDiscountProductCommand->getMaxDiscountAmount();
        if ($maxDiscountAmount === null && $discountAmount !== null) {
            $maxDiscountAmount = $discountAmount;
        }

        if ($maxDiscountAmount !== null) {
            $data['max_discount_amount'] = $maxDiscountAmount;
        }

        // Update the product with calculated data
        $this->repository->updateDiscountProduct($updateEcoDiscountProductCommand->getId(), $data);
    }
}
