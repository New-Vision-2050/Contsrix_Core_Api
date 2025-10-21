<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoProduct\DTO\Dashboard\CreateEcoProductDashboardDTO;
use Modules\Ecommerce\EcoProduct\DTO\Dashboard\CreateEcoProductNewDTO;
use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;

class CreateEcoProductDashboardRequest extends FormRequest
{

    public function rules(): array
    {
        // Get product ID if this is an update request
        $productId = request()->route('id');
        
        $rules = [
            // Multilingual name and description
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            
            // Categories and Brand
            'category_id' => 'required|uuid|exists:eco_categories,id',
            'sub_category_id' => 'nullable|uuid|exists:eco_categories,id',
            'sub_sub_category_id' => 'nullable|uuid|exists:eco_categories,id',
            'brand_id' => 'nullable|uuid|exists:eco_brands,id',
            
            // Countries
            'country_ids' => 'nullable|array',
            'country_ids.*' => 'exists:countries,id',
            
            // Product specifications
            'type' => 'required|in:digital,normal',
            'unit' => 'required|string|max:50',
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('eco_products', 'sku')->ignore($productId)
            ],
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'gender' => 'required|in:male,female,all',
            
            // Pricing and quantities
            'price' => 'required|numeric|min:0',
            'min_order_quantity' => 'required|integer|min:1',
            'stock' => 'nullable|integer|min:0',
            
            // Discount system
            'discount_type' => 'nullable|in:amount,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            
            // Tax and shipping
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'price_includes_vat' => 'boolean',
            'shipping_amount' => 'nullable|numeric|min:0',
            'shipping_included_in_price' => 'boolean',
            
            // Visibility
            'is_visible' => 'boolean',
            
            // Media
            'main_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'other_photos' => 'nullable|array|max:5',
            'other_photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            
            // Photo deletion (for updates - other photos only)
            'delete_photo_ids' => 'nullable|array',
            'delete_photo_ids.*' => 'integer|exists:media,id',
            
            // SEO
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ];

        return $rules;
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

            'price.required' => 'حقل السعر مطلوب',
            'price.numeric' => 'يجب أن يكون السعر رقم',
            'price.min' => 'يجب أن يكون السعر أكبر من أو يساوي صفر',

            'sku.required' => 'رمز المنتج مطلوب',
            'sku.string' => 'يجب أن يكون رمز المنتج نص',
            'sku.max' => 'يجب ألا يتجاوز رمز المنتج 255 حرف',
            'sku.unique' => 'رمز المنتج موجود مسبقاً، يرجى اختيار رمز آخر',

            // Categories validation
            'category_id.required' => 'حقل الفئة الرئيسية مطلوب',
            'category_id.uuid' => 'معرف الفئة الرئيسية غير صحيح',
            'category_id.exists' => 'الفئة الرئيسية المحددة غير موجودة',
            'sub_category_id.uuid' => 'معرف الفئة الفرعية غير صحيح',
            'sub_category_id.exists' => 'الفئة الفرعية المحددة غير موجودة',
            'sub_sub_category_id.uuid' => 'معرف الفئة الفرعية الثانية غير صحيح',
            'sub_sub_category_id.exists' => 'الفئة الفرعية الثانية المحددة غير موجودة',

            // Product specifications
            'type.required' => 'نوع المنتج مطلوب',
            'type.in' => 'نوع المنتج يجب أن يكون رقمي أو عادي',
            'unit.required' => 'وحدة القياس مطلوبة',
            'unit.string' => 'يجب أن تكون وحدة القياس نص',
            'gender.required' => 'الجنس المستهدف مطلوب',
            'gender.in' => 'الجنس المستهدف يجب أن يكون ذكر أو أنثى أو الكل',
            'min_order_quantity.required' => 'الحد الأدنى لكمية الطلب مطلوب',
            'min_order_quantity.integer' => 'يجب أن يكون الحد الأدنى لكمية الطلب رقم صحيح',
            'min_order_quantity.min' => 'يجب أن يكون الحد الأدنى لكمية الطلب 1 على الأقل',

            // Countries validation
            'country_ids.array' => 'يجب أن تكون معرفات الدول مصفوفة',
            'country_ids.*.uuid' => 'معرف الدولة يجب أن يكون UUID صحيح',
            'country_ids.*.exists' => 'الدولة المحددة غير موجودة',

            // Photo deletion validation messages (other photos only)
            'delete_photo_ids.array' => 'يجب أن تكون معرفات الصور المراد حذفها مصفوفة',
            'delete_photo_ids.*.integer' => 'معرف الصورة يجب أن يكون رقم صحيح',
            'delete_photo_ids.*.exists' => 'الصورة المحددة غير موجودة',

            'stock.integer' => __('ecoproduct::validation.stock_integer'),
            'stock.min' => __('ecoproduct::validation.stock_min'),

            'warehouse_id.required' => __('ecoproduct::validation.warehouse_id_required'),
            'warehouse_id.uuid' => __('ecoproduct::validation.warehouse_id_uuid'),
            'warehouse_id.exists' => __('ecoproduct::validation.warehouse_id_exists'),

            'requires_shipping.boolean' => __('ecoproduct::validation.requires_shipping_boolean'),
            'unlimited_quantity.boolean' => __('ecoproduct::validation.unlimited_quantity_boolean'),
            'is_taxable.boolean' => __('ecoproduct::validation.is_taxable_boolean'),
            'price_includes_vat.boolean' => __('ecoproduct::validation.price_includes_vat_boolean'),
            'shipping_included_in_price.boolean' => 'يجب أن يكون حقل السعر داخل الشحن صحيح أو خطأ',
            'product_included.boolean' => 'يجب أن يكون حقل داخل المنتج صحيح أو خطأ',
            'vat_percentage.numeric' => __('ecoproduct::validation.vat_percentage_numeric'),
            'vat_percentage.min' => __('ecoproduct::validation.vat_percentage_min'),
            'vat_percentage.max' => __('ecoproduct::validation.vat_percentage_max'),
            'is_visible.boolean' => __('ecoproduct::validation.is_visible_boolean'),

            // Multilingual Name
            'name.required' => 'حقل الاسم مطلوب',
            'name.array' => 'يجب أن يكون حقل الاسم مصفوفة تحتوي على اللغات',
            'name.ar.required' => 'الاسم باللغة العربية مطلوب',
            'name.ar.string' => 'يجب أن يكون الاسم العربي نص',
            'name.ar.max' => 'يجب ألا يتجاوز الاسم العربي 255 حرف',
            'name.en.required' => 'الاسم باللغة الإنجليزية مطلوب',
            'name.en.string' => 'يجب أن يكون الاسم الإنجليزي نص',
            'name.en.max' => 'يجب ألا يتجاوز الاسم الإنجليزي 255 حرف',

            // Multilingual Description
            'description.array' => 'يجب أن يكون حقل الوصف مصفوفة تحتوي على اللغات',
            'description.ar.string' => 'يجب أن يكون الوصف العربي نص',
            'description.en.string' => 'يجب أن يكون الوصف الإنجليزي نص',

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
     * Create a new DTO from the validated request data.
     */
    public function createNewEcoProductDTO(): CreateEcoProductNewDTO
    {
        $validatedData = $this->validated();

        return new CreateEcoProductNewDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $validatedData['name'],
            description: $validatedData['description'] ?? null,
            categoryId: Uuid::fromString($validatedData['category_id']),
            subCategoryId: !empty($validatedData['sub_category_id']) ? Uuid::fromString($validatedData['sub_category_id']) : null,
            subSubCategoryId: !empty($validatedData['sub_sub_category_id']) ? Uuid::fromString($validatedData['sub_sub_category_id']) : null,
            brandId: !empty($validatedData['brand_id']) ? Uuid::fromString($validatedData['brand_id']) : null,
            countryIds: $validatedData['country_ids'] ?? null,
            type: $validatedData['type'],
            unit: $validatedData['unit'],
            sku: $validatedData['sku'],
            warehouseId: Uuid::fromString($validatedData['warehouse_id']),
            gender: $validatedData['gender'],
            price: (float) $validatedData['price'],
            minOrderQuantity: (int) $validatedData['min_order_quantity'],
            stock: isset($validatedData['stock']) ? (int) $validatedData['stock'] : null,
            discountType: $validatedData['discount_type'] ?? null,
            discountValue: isset($validatedData['discount_value']) ? (float) $validatedData['discount_value'] : null,
            vatPercentage: isset($validatedData['vat_percentage']) ? (float) $validatedData['vat_percentage'] : null,
            priceIncludesVat: (bool) ($validatedData['price_includes_vat'] ?? false),
            shippingAmount: isset($validatedData['shipping_amount']) ? (float) $validatedData['shipping_amount'] : null,
            shippingIncludedInPrice: (bool) ($validatedData['shipping_included_in_price'] ?? false),
            isVisible: (bool) ($validatedData['is_visible'] ?? true),
            mainPhoto: null, // Will be handled by FileUploadService in the service
            otherPhotos: null, // Will be handled by FileUploadService in the service
            metaTitle: $validatedData['meta_title'] ?? null,
            metaDescription: $validatedData['meta_description'] ?? null,
            metaKeywords: $validatedData['meta_keywords'] ?? null,
        );
    }

    /**
     * Handle main photo upload
     */
    private function handleMainPhoto(): ?array
    {
        if ($this->hasFile('main_photo') && $this->file('main_photo')->isValid()) {
            $file = $this->file('main_photo');
            
            // Generate unique filename to avoid conflicts
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products/main', $filename, 'public');
            
            return [
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'path' => $path,
                'url' => asset('storage/' . $path),
            ];
        }
        return null;
    }

    /**
     * Handle other photos upload
     */
    private function handleOtherPhotos(): ?array
    {
        if ($this->hasFile('other_photos')) {
            $photos = [];
            $files = $this->file('other_photos');
            
            // Handle both single file and array of files
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    // Generate unique filename
                    $filename = time() . '_' . $index . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('products/gallery', $filename, 'public');
                    
                    $photos[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'filename' => $filename,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                    ];
                }
            }
            
            return !empty($photos) ? $photos : null;
        }
        return null;
    }
}
