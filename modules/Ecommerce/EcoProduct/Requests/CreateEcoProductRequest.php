<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoProduct\DTO\CreateEcoProductDTO;
use Illuminate\Validation\Rules\File;

class CreateEcoProductRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0'],
            'sku' => ['required', 'string', 'max:255', 'unique:eco_products,sku'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],

            'requires_shipping' => ['boolean'],
            'unlimited_quantity' => ['boolean'],
            'is_taxable' => ['boolean'],
            'price_includes_vat' => ['boolean'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_visible' => ['boolean'],
            'category_id' => ['required', 'uuid', 'exists:eco_categories,id'],
            'brand_id' => ['nullable', 'uuid', 'exists:eco_brands,id'],
            'sub_category_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'main_image' => [
                'nullable', // Main image is mandatory
                // File::image()
                //     ->min(10) // Minimum 10 KB
                //     ->max(2 * 1024) // Maximum 2 MB (2 * 1024 KB)
                //     ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)), // Max dimensions
            ],
            'other_images' => ['nullable', 'array', 'max:5'], // Allow up to 5 additional images
            'other_images.*' => [ // Rules for each item in the 'other_images' array
                // File::image()
                //     ->min(10)
                //     ->max(2 * 1024)
                //     ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)),
            ],
            "type" => ['required', 'string', 'max:255'],
            // Multilingual Name
            'name' => ['required', 'string'],
            // 'name.ar' => ['required', 'string', 'max:255'],
            // 'name.en' => ['nullable', 'string', 'max:255'],

            // Multilingual Description
            'description' => ['nullable', 'string'],
            // 'description.ar' => ['required_with:description', 'string', 'max:1000'],
            // 'description.en' => ['nullable', 'string', 'max:1000'],

            // Product Taxes (array of tax objects)
            'taxes' => ['nullable', 'array'],
            'taxes.*.country_id' => ['nullable',  'exists:countries,id'],
            'taxes.*.tax_number' => ['nullable', 'string', 'max:255'],
            'taxes.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'taxes.*.is_active' => ['boolean'],

            // Product Details (array of label-value pairs)
            'details' => ['nullable', 'array'],
            'details.*.label' => ['required_with:details', 'string', 'max:255'],
            'details.*.value' => ['required_with:details', 'string', 'max:255'],

            // Product Custom Fields (array of field_name-field_value pairs)
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.field_name' => ['required_with:custom_fields', 'string', 'max:255'],
            'custom_fields.*.field_value' => ['required_with:custom_fields', 'string', 'max:2000'],

            // Product SEO (single object)
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:2000'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],
            'associated_product_ids' => ['nullable', 'array'],
            'associated_product_ids.*' => ['uuid', 'exists:eco_products,id'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // EcoProduct main fields
            'company_id.required' => __('ecoproduct::validation.company_id_required'),
            'company_id.uuid' => __('ecoproduct::validation.company_id_uuid'),
            'company_id.exists' => __('ecoproduct::validation.company_id_exists'),

            'price.required' => __('ecoproduct::validation.price_required'),
            'price.numeric' => __('ecoproduct::validation.price_numeric'),
            'price.min' => __('ecoproduct::validation.price_min'),

            'sku.required' => __('ecoproduct::validation.sku_required'),
            'sku.string' => __('ecoproduct::validation.sku_string'),
            'sku.max' => __('ecoproduct::validation.sku_max'),
            'sku.unique' => __('ecoproduct::validation.sku_unique'),

            'stock.integer' => __('ecoproduct::validation.stock_integer'),
            'stock.min' => __('ecoproduct::validation.stock_min'),

            'warehouse_id.required' => __('ecoproduct::validation.warehouse_id_required'),
            'warehouse_id.uuid' => __('ecoproduct::validation.warehouse_id_uuid'),
            'warehouse_id.exists' => __('ecoproduct::validation.warehouse_id_exists'),

            'requires_shipping.boolean' => __('ecoproduct::validation.requires_shipping_boolean'),
            'unlimited_quantity.boolean' => __('ecoproduct::validation.unlimited_quantity_boolean'),
            'is_taxable.boolean' => __('ecoproduct::validation.is_taxable_boolean'),
            'price_includes_vat.boolean' => __('ecoproduct::validation.price_includes_vat_boolean'),
            'vat_percentage.numeric' => __('ecoproduct::validation.vat_percentage_numeric'),
            'vat_percentage.min' => __('ecoproduct::validation.vat_percentage_min'),
            'vat_percentage.max' => __('ecoproduct::validation.vat_percentage_max'),
            'is_visible.boolean' => __('ecoproduct::validation.is_visible_boolean'),

            // Multilingual Name
            'name.required' => __('ecoproduct::validation.name_required'),
            'name.array' => __('ecoproduct::validation.name_array'),
            'name.ar.required' => __('ecoproduct::validation.name_ar_required'),
            'name.ar.string' => __('ecoproduct::validation.name_ar_string'),
            'name.ar.max' => __('ecoproduct::validation.name_ar_max'),
            'name.en.string' => __('ecoproduct::validation.name_en_string'),
            'name.en.max' => __('ecoproduct::validation.name_en_max'),

            // Multilingual Description
            'description.array' => __('ecoproduct::validation.description_array'),
            'description.ar.required_with' => __('ecoproduct::validation.description_ar_required_with'),
            'description.ar.string' => __('ecoproduct::validation.description_ar_string'),
            'description.ar.max' => __('ecoproduct::validation.description_ar_max'),
            'description.en.string' => __('ecoproduct::validation.description_en_string'),
            'description.en.max' => __('ecoproduct::validation.description_en_max'),

            // Product Taxes
            'taxes.array' => __('ecoproduct::validation.taxes_array'),
            'taxes.*.country_id.exists' => __('ecoproduct::validation.taxes_country_id_exists'),
            'taxes.*.tax_number.string' => __('ecoproduct::validation.taxes_tax_number_string'),
            'taxes.*.tax_number.max' => __('ecoproduct::validation.taxes_tax_number_max'),
            'taxes.*.tax_percentage.numeric' => __('ecoproduct::validation.taxes_tax_percentage_numeric'),
            'taxes.*.tax_percentage.min' => __('ecoproduct::validation.taxes_tax_percentage_min'),
            'taxes.*.tax_percentage.max' => __('ecoproduct::validation.taxes_tax_percentage_max'),
            'taxes.*.is_active.boolean' => __('ecoproduct::validation.taxes_is_active_boolean'),

            // Product Details
            'details.array' => __('ecoproduct::validation.details_array'),
            'details.*.label.required_with' => __('ecoproduct::validation.details_label_required_with'),
            'details.*.label.string' => __('ecoproduct::validation.details_label_string'),
            'details.*.label.max' => __('ecoproduct::validation.details_label_max'),
            'details.*.value.required_with' => __('ecoproduct::validation.details_value_required_with'),
            'details.*.value.string' => __('ecoproduct::validation.details_value_string'),
            'details.*.value.max' => __('ecoproduct::validation.details_value_max'),

            // Product Custom Fields
            'custom_fields.array' => __('ecoproduct::validation.custom_fields_array'),
            'custom_fields.*.field_name.required_with' => __('ecoproduct::validation.custom_fields_field_name_required_with'),
            'custom_fields.*.field_name.string' => __('ecoproduct::validation.custom_fields_field_name_string'),
            'custom_fields.*.field_name.max' => __('ecoproduct::validation.custom_fields_field_name_max'),
            'custom_fields.*.field_value.required_with' => __('ecoproduct::validation.custom_fields_field_value_required_with'),
            'custom_fields.*.field_value.string' => __('ecoproduct::validation.custom_fields_field_value_string'),
            'custom_fields.*.field_value.max' => __('ecoproduct::validation.custom_fields_field_value_max'),

            // Product SEO
            'seo.array' => __('ecoproduct::validation.seo_array'),
            'seo.meta_title.string' => __('ecoproduct::validation.seo_meta_title_string'),
            'seo.meta_title.max' => __('ecoproduct::validation.seo_meta_title_max'),
            'seo.meta_description.string' => __('ecoproduct::validation.seo_meta_description_string'),
            'seo.meta_description.max' => __('ecoproduct::validation.seo_meta_description_max'),
            'seo.meta_keywords.string' => __('ecoproduct::validation.seo_meta_keywords_string'),
            'seo.meta_keywords.max' => __('ecoproduct::validation.seo_meta_keywords_max'),

            //associated_products
            'associated_product_ids.array' => __('ecoproduct::validation.associated_product_ids_array'),
            'associated_product_ids.*.uuid' => __('ecoproduct::validation.associated_product_ids_uuid'),
            'associated_product_ids.*.exists' => __('ecoproduct::validation.associated_product_ids_exists'),

            // Product Images
            'main_image.required' => __('ecoproduct::validation.main_image_required'),
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
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createCreateEcoProductDTO(): CreateEcoProductDTO
    {
        $validatedData = $this->validated();

        $description = $validatedData['description'] ?? null;
        if (is_array($description) && empty(array_filter($description))) {
            $description = null;
        }

        $seoData = $validatedData['seo'] ?? null;
        if (is_array($seoData) && empty(array_filter($seoData))) {
            $seoData = null;
        }

        return new CreateEcoProductDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $validatedData['name'],
            description: $description,
            price: (float) $validatedData['price'],
            sku: $validatedData['sku'],
            stock: (int)$validatedData['stock'] ?? null,
            warehouseId: Uuid::fromString($validatedData['warehouse_id']),
            requiresShipping: (bool)$validatedData['requires_shipping'] ?? 1,
            unlimitedQuantity: (bool)$validatedData['unlimited_quantity'] ?? 0,
            isTaxable: (bool)$validatedData['is_taxable'] ?? true,
            priceIncludesVat: (bool)$validatedData['price_includes_vat'] ?? 0,
            vatPercentage: (float)$validatedData['vat_percentage'] ?? null,
            isVisible: (bool)$validatedData['is_visible'] ?? true,
            brandId: !empty($validatedData['brand_id']) ? Uuid::fromString($validatedData['brand_id']) : null,
            categoryId: !empty($validatedData['category_id']) ? Uuid::fromString($validatedData['category_id']) : null,
            subCategoryId: !empty($validatedData['sub_category_id']) ? Uuid::fromString($validatedData['sub_category_id']) : null,
            type: $validatedData['type'] ?? null,
            taxes: $validatedData['taxes'] ?? null,
            details: $validatedData['details'] ?? null,
            customFields: $validatedData['custom_fields'] ?? null,
            seo: $seoData,
            associatedProductIds: $validatedData['associated_product_ids'] ?? [],
            mainImage: $this->file('main_image'), // NEW: Pass UploadedFile object
            otherImages: $this->file('other_images') ?? [], // NEW: Pass array of UploadedFile objects
        );
    }
}
