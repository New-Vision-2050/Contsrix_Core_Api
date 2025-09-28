<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Commands\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoAppSettingDashboardCommand
{
    public function __construct(
        private UuidInterface $id,
        
        // Theme & UI Settings
        private ?string $backgroundColor = null,
        private ?bool $enableSearch = null,
        
        // First page settings
        private ?bool $showLogoOnFirstPage = null,
        
        // Front page settings
        private ?bool $showLogoOnFrontPage = null,
        private ?int $countPhotos = null,
        
        // Display products
        private ?bool $productDisplayCategory = null,
        private ?string $productDisplayType = null,
        private ?int $productColumnsCount = null,
        private ?int $productRowsCount = null,
        private ?bool $showProductsInApp = null,
        
        // Display favorites
        private ?bool $showFavoritesSearch = null,
        private ?bool $showFavoritesDelete = null,
        private ?bool $showFavoritesProducts = null,
        private ?string $favoritesDisplayType = null,
        private ?bool $showFavoritesInApp = null,
        
        // Product selection settings
        private ?bool $showProductImage = null,
        private ?bool $showProductRating = null,
        private ?bool $showSimilarProducts = null,
        private ?bool $showProductPrice = null,
        private ?bool $showProductShipping = null,
        private ?bool $showProductDescription = null,
        private ?bool $showProductColorCode = null,
        private ?bool $showProductSize = null,
        private ?bool $showProductComment = null,
        private ?bool $canProductComment = null,
        
        // Cart settings
        private ?bool $showCartProducts = null,
        private ?string $cartDisplayType = null,
        private ?int $cartColumnsCount = null,
        private ?bool $showCartInApp = null,
        
        // Product card settings
        private ?bool $showProductName = null,
        private ?bool $showProductDescriptionCard = null,
        private ?bool $showProductPriceCard = null,
        private ?bool $showProductColor = null,
        private ?bool $showProductSizeCard = null,
        private ?bool $showSimilarProductsCard = null,
        private ?string $productCardDisplayType = null,
        private ?int $productCardColumnsCount = null,
        private ?bool $showDiscountCode = null,
        private ?bool $showPaymentDetails = null,
        private ?bool $showProductCardInApp = null,
        
        // Filter settings
        private ?bool $showFilterInApp = null,
        private ?bool $showCategoryFilter = null,
        private ?bool $showProductFilter = null,
        private ?bool $showColorFilter = null,
        private ?bool $showBrandFilter = null,
        private ?bool $showSizeFilter = null,
        private ?bool $showPriceFilter = null,
        private ?bool $showRatingFilter = null,
        private ?bool $showDiscountFilter = null,
        
        // Terms and Conditions settings
        private ?bool $showTermsText = null,
        private ?bool $showPrivacyPolicy = null,
        private ?bool $showReturnPolicy = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        $data = [];

        // Add non-null values
        if ($this->backgroundColor !== null) $data['background_color'] = $this->backgroundColor;
        if ($this->enableSearch !== null) $data['enable_search'] = (int) $this->enableSearch;
        if ($this->showLogoOnFirstPage !== null) $data['show_logo_on_first_page'] = (int) $this->showLogoOnFirstPage;
        if ($this->showLogoOnFrontPage !== null) $data['show_logo_on_front_page'] = (int) $this->showLogoOnFrontPage;
        if ($this->countPhotos !== null) $data['count_photos'] = $this->countPhotos;
        if ($this->productDisplayCategory !== null) $data['product_display_category'] = (int) $this->productDisplayCategory;
        if ($this->productDisplayType !== null) $data['product_display_type'] = $this->productDisplayType;
        if ($this->productColumnsCount !== null) $data['product_columns_count'] = $this->productColumnsCount;
        if ($this->productRowsCount !== null) $data['product_rows_count'] = $this->productRowsCount;
        if ($this->showProductsInApp !== null) $data['show_products_in_app'] = (int) $this->showProductsInApp;
        if ($this->showFavoritesSearch !== null) $data['show_favorites_search'] = (int) $this->showFavoritesSearch;
        if ($this->showFavoritesDelete !== null) $data['show_favorites_delete'] = (int) $this->showFavoritesDelete;
        if ($this->showFavoritesProducts !== null) $data['show_favorites_products'] = (int) $this->showFavoritesProducts;
        if ($this->favoritesDisplayType !== null) $data['favorites_display_type'] = $this->favoritesDisplayType;
        if ($this->showFavoritesInApp !== null) $data['show_favorites_in_app'] = (int) $this->showFavoritesInApp;
        if ($this->showProductImage !== null) $data['show_product_image'] = (int) $this->showProductImage;
        if ($this->showProductRating !== null) $data['show_product_rating'] = (int) $this->showProductRating;
        if ($this->showSimilarProducts !== null) $data['show_similar_products'] = (int) $this->showSimilarProducts;
        if ($this->showProductPrice !== null) $data['show_product_price'] = (int) $this->showProductPrice;
        if ($this->showProductShipping !== null) $data['show_product_shipping'] = (int) $this->showProductShipping;
        if ($this->showProductDescription !== null) $data['show_product_description'] = (int) $this->showProductDescription;
        if ($this->showProductColorCode !== null) $data['show_product_color_code'] = (int) $this->showProductColorCode;
        if ($this->showProductSize !== null) $data['show_product_size'] = (int) $this->showProductSize;
        if ($this->showProductComment !== null) $data['show_product_comment'] = (int) $this->showProductComment;
        if ($this->canProductComment !== null) $data['can_product_comment'] = (int) $this->canProductComment;
        if ($this->showCartProducts !== null) $data['show_cart_products'] = (int) $this->showCartProducts;
        if ($this->cartDisplayType !== null) $data['cart_display_type'] = $this->cartDisplayType;
        if ($this->cartColumnsCount !== null) $data['cart_columns_count'] = $this->cartColumnsCount;
        if ($this->showCartInApp !== null) $data['show_cart_in_app'] = (int) $this->showCartInApp;
        if ($this->showProductName !== null) $data['show_product_name'] = (int) $this->showProductName;
        if ($this->showProductDescriptionCard !== null) $data['show_product_description_card'] = (int) $this->showProductDescriptionCard;
        if ($this->showProductPriceCard !== null) $data['show_product_price_card'] = (int) $this->showProductPriceCard;
        if ($this->showProductColor !== null) $data['show_product_color'] = (int) $this->showProductColor;
        if ($this->showProductSizeCard !== null) $data['show_product_size_card'] = (int) $this->showProductSizeCard;
        if ($this->showSimilarProductsCard !== null) $data['show_similar_products_card'] = (int) $this->showSimilarProductsCard;
        if ($this->productCardDisplayType !== null) $data['product_card_display_type'] = $this->productCardDisplayType;
        if ($this->productCardColumnsCount !== null) $data['product_card_columns_count'] = $this->productCardColumnsCount;
        if ($this->showDiscountCode !== null) $data['show_discount_code'] = (int) $this->showDiscountCode;
        if ($this->showPaymentDetails !== null) $data['show_payment_details'] = (int) $this->showPaymentDetails;
        if ($this->showProductCardInApp !== null) $data['show_product_card_in_app'] = (int) $this->showProductCardInApp;
        if ($this->showFilterInApp !== null) $data['show_filter_in_app'] = (int) $this->showFilterInApp;
        if ($this->showCategoryFilter !== null) $data['show_category_filter'] = (int) $this->showCategoryFilter;
        if ($this->showProductFilter !== null) $data['show_product_filter'] = (int) $this->showProductFilter;
        if ($this->showColorFilter !== null) $data['show_color_filter'] = (int) $this->showColorFilter;
        if ($this->showBrandFilter !== null) $data['show_brand_filter'] = (int) $this->showBrandFilter;
        if ($this->showSizeFilter !== null) $data['show_size_filter'] = (int) $this->showSizeFilter;
        if ($this->showPriceFilter !== null) $data['show_price_filter'] = (int) $this->showPriceFilter;
        if ($this->showRatingFilter !== null) $data['show_rating_filter'] = (int) $this->showRatingFilter;
        if ($this->showDiscountFilter !== null) $data['show_discount_filter'] = (int) $this->showDiscountFilter;
        if ($this->showTermsText !== null) $data['show_terms_text'] = (int) $this->showTermsText;
        if ($this->showPrivacyPolicy !== null) $data['show_privacy_policy'] = (int) $this->showPrivacyPolicy;
        if ($this->showReturnPolicy !== null) $data['show_return_policy'] = (int) $this->showReturnPolicy;

        return $data;
    }
}
