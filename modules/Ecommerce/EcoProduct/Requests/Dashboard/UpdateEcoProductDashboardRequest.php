<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoProduct\Commands\Dashboard\UpdateEcoProductDashboardCommand;
use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;

class UpdateEcoProductDashboardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('id'); // Get the product ID from the route for unique checks

        return [
            // EcoProduct main fields
            'price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('eco_products')->ignore($productId)],
            'stock' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],

            'requires_shipping' => ['nullable', 'boolean'],
            'unlimited_quantity' => ['nullable', 'boolean'],
            'is_taxable' => ['nullable', 'boolean'],
            'price_includes_vat' => ['nullable', 'boolean'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],

            'category_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'brand_id' => ['nullable', 'uuid', 'exists:eco_brands,id'],
            'sub_category_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'type' => ['nullable', 'in:digital,normal'],
            'unit' => 'required_if:type,normal|nullable|string|max:50',
            'gender' => ['nullable', 'in:male,female,all'],

            // Multilingual Name
            'name' => ['required', 'string'],

            // Multilingual Description
            'description' => ['required', 'string'],

            // Product Taxes (for syncing existing or adding new ones)
            'taxes' => ['nullable', 'array'],
            // If you want to allow updating specific tax entries by ID, uncomment these:
            // 'taxes.*.id' => ['nullable', 'uuid', Rule::exists('product_taxes', 'id')->where('product_id', $productId)],
            'taxes.*.country_id' => ['required_with:taxes.*', 'exists:countries,id'], // Corrected: Added 'uuid'
            'taxes.*.tax_number' => ['nullable', 'string', 'max:255'],
            'taxes.*.tax_percentage' => ['required_with:taxes.*', 'numeric', 'min:0', 'max:100'],
            'taxes.*.is_active' => ['boolean'],

            // Product Details (for syncing existing or adding new ones)
            'details' => ['nullable', 'array'],
            // 'details.*.id' => ['nullable', 'uuid', Rule::exists('product_details', 'id')->where('product_id', $productId)],
            'details.*.label' => ['required_with:details.*', 'string', 'max:255'],
            'details.*.value' => ['required_with:details.*', 'string', 'max:255'],

            // Product Custom Fields
            'custom_fields' => ['nullable', 'array'],
            // 'custom_fields.*.id' => ['nullable', 'uuid', Rule::exists('product_custom_fields', 'id')->where('product_id', $productId)],
            'custom_fields.*.field_name' => ['required_with:custom_fields.*', 'string', 'max:255'],
            'custom_fields.*.field_value' => ['required_with:custom_fields.*', 'string', 'max:2000'],

            // Product SEO (single object, so usually replaced or updated)
            'seo' => ['nullable', 'array'],
            // 'seo.id' => ['nullable', 'uuid', Rule::exists('product_seo', 'id')->where('product_id', $productId)],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:2000'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],

            // Associated Products (for syncing)
            'associated_product_ids' => ['nullable', 'array'],
            'associated_product_ids.*' => ['uuid', 'exists:eco_products,id'],

            // Video
            'video_url' => ['nullable', 'url', 'max:500'],

            // Images (nullable for updates)
            'main_image' => [
                'nullable', // Main image is optional for update
                // File::image()
                //     ->min(10)
                //     ->max(2 * 1024)
                //     ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)),
            ],
            'delete_main_image' => ['nullable', 'boolean'],
            'other_images' => ['nullable', 'array'],
            'other_images.*' => [
                // File::image()
                //     ->min(10)
                //     ->max(2 * 1024)
                //     ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)),
            ],
            'other_images_to_delete' => ['nullable', 'array'], // IDs of existing images to delete
            'other_images_to_delete.*' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            // ... (your existing messages) ...
            'sku.unique' => __('ecoproduct::validation.sku_unique'),

            'category_id.uuid' => __('ecoproduct::validation.category_id_uuid'),
            'category_id.exists' => __('ecoproduct::validation.category_id_exists'),
            'brand_id.uuid' => __('ecoproduct::validation.brand_id_uuid'),
            'brand_id.exists' => __('ecoproduct::validation.brand_id_exists'),
            'sub_category_id.uuid' => __('ecoproduct::validation.sub_category_id_uuid'),
            'sub_category_id.exists' => __('ecoproduct::validation.sub_category_id_exists'),
            'type.in' => 'نوع المنتج يجب أن يكون رقمي أو عادي',
            'unit.required_if' => 'وحدة القياس مطلوبة للمنتجات العادية',
            'unit.string' => 'يجب أن تكون وحدة القياس نص',
            'unit.max' => 'يجب ألا تتجاوز وحدة القياس 50 حرف',
            'gender.in' => 'الجنس المستهدف يجب أن يكون ذكر أو أنثى أو الكل',

            'taxes.*.country_id.required_with' => __('ecoproduct::validation.taxes_country_id_required_with'),
            'taxes.*.country_id.uuid' => __('ecoproduct::validation.taxes_country_id_uuid'),
            'taxes.*.country_id.exists' => __('ecoproduct::validation.taxes_country_id_exists'),
            'taxes.*.tax_percentage.required_with' => __('ecoproduct::validation.taxes_tax_percentage_required_with'),

            'details.*.label.required_with' => __('ecoproduct::validation.details_label_required_with'),
            'details.*.value.required_with' => __('ecoproduct::validation.details_value_required_with'),

            'custom_fields.*.field_name.required_with' => __('ecoproduct::validation.custom_fields_field_name_required_with'),
            'custom_fields.*.field_value.required_with' => __('ecoproduct::validation.custom_fields_field_value_required_with'),

            // Image messages
            // 'main_image.required' is removed as it's nullable
            'main_image.image' => __('ecoproduct::validation.main_image_image'),
            'main_image.min' => __('ecoproduct::validation.main_image_min'),
            'main_image.max' => __('ecoproduct::validation.main_image_max'),
            'main_image.dimensions' => __('ecoproduct::validation.main_image_dimensions'),

            'other_images.array' => __('ecoproduct::validation.other_images_array'),
            'other_images.max' => __('ecoproduct::validation.other_images_max'),
            'other_images.*.image' => __('ecoproduct::validation.other_images_item_image'),
            'other_images.*.min' => __('ecoproduct::validation.other_images_item_min'),
            'other_images.*.max' => __('ecoproduct::validation.other_images_item_max'),
            'other_images.*.dimensions' => __('ecoproduct::validation.other_images_item_dimensions'),
            'other_images_to_delete.array' => __('ecoproduct::validation.other_images_to_delete_array'),
            'other_images_to_delete.*.uuid' => __('ecoproduct::validation.other_images_to_delete_uuid'),
            'other_images_to_delete.*.exists' => __('ecoproduct::validation.other_images_to_delete_exists'),
            'delete_main_image.boolean' => __('ecoproduct::validation.delete_main_image_boolean'),
        ];
    }

    /**
     * Create an UpdateEcoProductCommand from the validated request data.
     */
    public function createUpdateEcoProductCommand(): UpdateEcoProductDashboardCommand
    {
        $validatedData = $this->validated();
        $productId = Uuid::fromString($this->route('id'));

        // Handle description array to be null if empty or contains only nulls
        $description = $validatedData['description'] ?? null;
        if (is_array($description) && empty(array_filter($description))) {
            $description = null;
        }

        // Handle SEO array to be null if empty or contains only nulls
        $seoData = $validatedData['seo'] ?? null;
        if (is_array($seoData) && empty(array_filter($seoData))) {
            $seoData = null;
        }

        return new UpdateEcoProductDashboardCommand(
            id: $productId,
            name: $validatedData['name'],
            description: $description,
            price: isset($validatedData['price']) ? (float)$validatedData['price'] : null,
            sku: $validatedData['sku'] ?? null,
            stock: isset($validatedData['stock']) ? (int)$validatedData['stock'] : null,
            warehouseId: !empty($validatedData['warehouse_id'])? Uuid::fromString($validatedData['warehouse_id']): null,
            requiresShipping: (bool)($validatedData['requires_shipping'] ?? 1),
            unlimitedQuantity: (bool)($validatedData['unlimited_quantity'] ?? 0),
            isTaxable: (bool)($validatedData['is_taxable'] ?? true),
            priceIncludesVat: (bool)($validatedData['price_includes_vat'] ?? 0),
            vatPercentage: isset($validatedData['vat_percentage']) ? (float)$validatedData['vat_percentage'] : null,
            isVisible: (bool)($validatedData['is_visible'] ?? true),
            brandId: !empty($validatedData['brand_id']) ? Uuid::fromString($validatedData['brand_id']) : null,
            categoryId: !empty($validatedData['category_id']) ? Uuid::fromString($validatedData['category_id']) : null,
            subCategoryId: !empty($validatedData['sub_category_id']) ? Uuid::fromString($validatedData['sub_category_id']) : null,
            type: $validatedData['type'] ?? null,
            unit: $validatedData['unit'] ?? null,
            gender: $validatedData['gender'] ?? null,
            taxes: $validatedData['taxes'] ?? null,
            details: $validatedData['details'] ?? null,
            customFields: $validatedData['custom_fields'] ?? null,
            seo: $seoData,
            associatedProductIds: $validatedData['associated_product_ids'] ?? [],
            mainImage: $this->file('main_image'),
            otherImages: $this->file('other_images') ?? [],
            otherImagesToDelete: $validatedData['other_images_to_delete'] ?? [],
            deleteMainImage: (bool)($validatedData['delete_main_image'] ?? 0),
        );
    }
}