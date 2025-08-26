<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoProduct\Commands\UpdateEcoProductCommand;
// use Modules\Ecommerce\EcoProduct\Handlers\UpdateEcoProductHandler; // Handler is not needed in the request

class UpdateEcoProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Implement proper authorization logic here.
        // E.g., return auth()->user()->can('update', EcoProduct::class, $this->route('id'));
        return true;
    }

    public function rules(): array
    {

        $productId = Uuid::fromString($this->route('id'));
        return [
            'price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:eco_products,sku,' . $productId],
            'stock' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],

            'requires_shipping' => ['nullable', 'boolean'],
            'unlimited_quantity' => ['nullable', 'boolean'],
            'is_taxable' => ['nullable', 'boolean'],
            'price_includes_vat' => ['nullable', 'boolean'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],

            // Multilingual Name
            'name' => ['nullable', 'array'], // Name array itself is optional
            'name.ar' => ['required_with:name', 'string', 'max:255'], // Required if 'name' array is present
            'name.en' => ['nullable', 'string', 'max:255'], // English name is optional

            // Multilingual Description
            'description' => ['nullable', 'array'], // Description array itself is optional
            'description.ar' => ['required_with:description', 'string', 'max:1000'], // Required if 'description' array is present
            'description.en' => ['nullable', 'string', 'max:1000'],

            // Product Taxes (array of tax objects for updating/adding)
            // For complex updates (e.g., delete specific, update specific by ID),
            // you might need more granular rules and logic in the service.
            'taxes' => ['nullable', 'array'],
            // If updating existing taxes, you might include 'taxes.*.id' rule here
            // 'taxes.*.id' => ['nullable', 'uuid', 'exists:product_taxes,id'],
            'taxes.*.country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'taxes.*.tax_number' => ['nullable', 'string', 'max:255'],
            'taxes.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'taxes.*.is_active' => ['boolean'],

            // Product Details
            'details' => ['nullable', 'array'],
            // 'details.*.id' => ['nullable', 'integer', 'exists:product_details,id'], // If updating existing details
            'details.*.label' => ['required_with:details', 'string', 'max:255'],
            'details.*.value' => ['required_with:details', 'string', 'max:255'],

            // Product Custom Fields
            'custom_fields' => ['nullable', 'array'],
            // 'custom_fields.*.id' => ['nullable', 'integer', 'exists:product_custom_fields,id'], // If updating existing custom fields
            'custom_fields.*.field_name' => ['required_with:custom_fields', 'string', 'max:255'],
            'custom_fields.*.field_value' => ['required_with:custom_fields', 'string', 'max:2000'],

            // Product SEO (single object)
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:2000'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.numeric' => __('ecoproduct::validation.price_numeric'),
            'price.min' => __('ecoproduct::validation.price_min'),

            'sku.string' => __('ecoproduct::validation.sku_string'),
            'sku.max' => __('ecoproduct::validation.sku_max'),
            'sku.unique' => __('ecoproduct::validation.sku_unique'), // Uses the custom message for unique rule

            'stock.integer' => __('ecoproduct::validation.stock_integer'),
            'stock.min' => __('ecoproduct::validation.stock_min'),

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
            'name.array' => __('ecoproduct::validation.name_array'),
            'name.ar.required_with' => __('ecoproduct::validation.name_ar_required_with'),
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
            'taxes.*.country_id.uuid' => __('ecoproduct::validation.taxes_country_id_uuid'),
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
        ];
    }

  public function createUpdateEcoProductCommand(): UpdateEcoProductCommand
    {
        $validatedData = $this->validated();

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

        return new UpdateEcoProductCommand(
            id: Uuid::fromString($this->route('id')),
            name: $validatedData['name'] ?? null,
            description: $description,
            price: $validatedData['price'] ?? null,
            sku: $validatedData['sku'] ?? null,
            stock: $validatedData['stock'] ?? null,
            warehouseId: (isset($validatedData['warehouse_id'])) ? Uuid::fromString($validatedData['warehouse_id']) : null, // Handle nullable UuidInterface
            requiresShipping: $validatedData['requires_shipping'] ?? null,
            unlimitedQuantity: $validatedData['unlimited_quantity'] ?? null,
            isTaxable: $validatedData['is_taxable'] ?? null,
            priceIncludesVat: $validatedData['price_includes_vat'] ?? null,
            vatPercentage: $validatedData['vat_percentage'] ?? null,
            isVisible: $validatedData['is_visible'] ?? null,
            taxes: $validatedData['taxes'] ?? null,
            details: $validatedData['details'] ?? null,
            customFields: $validatedData['custom_fields'] ?? null,
            seo: $seoData,
        );
    }
}
