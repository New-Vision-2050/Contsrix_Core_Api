<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoProduct\DTO\CreateEcoProductDTO;

class CreateEcoProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Implement proper authorization logic here.
        // E.g., return auth()->user()->can('create', EcoProduct::class);
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid'],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'array'], // Description is optional
            'description.ar' => ['required_with:description', 'string', 'max:1000'], // Required if 'description' array is present
            'description.en' => ['nullable', 'string', 'max:1000'],

            'price' => ['required', 'numeric', 'min:0'],
            'sku' => ['required', 'string', 'max:255', 'unique:eco_products,sku'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['required', 'uuid'], // Validate warehouse_id as UUID

            'requires_shipping' => ['boolean'],
            'unlimited_quantity' => ['boolean'],
            'is_taxable' => ['boolean'],
            'price_includes_vat' => ['boolean'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_visible' => ['boolean'],

            'details' => ['nullable', 'array'],
            'details.*.label' => ['required_with:details', 'string', 'max:255'],
            'details.*.value' => ['required_with:details', 'string', 'max:255'],


            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.field_name' => ['required_with:custom_fields', 'string', 'max:255'],
            'custom_fields.*.field_value' => ['required_with:custom_fields', 'string', 'max:255'],

            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:1000'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => __('ecoproduct::validation.company_id_required'),
            'company_id.uuid' => __('ecoproduct::validation.company_id_uuid'),
            // Name messages
            'name.required' => __('ecoproduct::validation.name_required'),
            'name.array' => __('ecoproduct::validation.name_array'),
            'name.en.string' => __('ecoproduct::validation.name_en_string'),
            'name.en.max' => __('ecoproduct::validation.name_en_max'),
            'name.ar.required' => __('ecoproduct::validation.name_ar_required'),
            'name.ar.string' => __('ecoproduct::validation.name_ar_string'),
            'name.ar.max' => __('ecoproduct::validation.name_ar_max'),

            // Description messages
            'description.array' => __('ecoproduct::validation.description_array'),
            'description.ar.required_with' => __('ecoproduct::validation.description_ar_required_with'),
            'description.ar.string' => __('ecoproduct::validation.description_ar_string'),
            'description.ar.max' => __('ecoproduct::validation.description_ar_max'),
            'description.en.string' => __('ecoproduct::validation.description_en_string'),
            'description.en.max' => __('ecoproduct::validation.description_en_max'),

            'price.required' => __('ecoproduct::validation.price_required'),
            'price.numeric' => __('ecoproduct::validation.price_numeric'),
            'price.min' => __('ecoproduct::validation.price_min'),
            'sku.required' => __('ecoproduct::validation.sku_required'),
            'sku.unique' => __('ecoproduct::validation.sku_unique'),
            'stock.integer' => __('ecoproduct::validation.stock_integer'),
            'stock.min' => __('ecoproduct::validation.stock_min'),
            'warehouse_id.required' => __('ecoproduct::validation.warehouse_id_required'),
            'warehouse_id.uuid' => __('ecoproduct::validation.warehouse_id_uuid'),
        ];
    }


    public function createCreateEcoProductDTO(): CreateEcoProductDTO
    {
        $validatedData = $this->validated();

        $description = $validatedData['description'] ?? null;
        if (is_array($description) && empty(array_filter($description))) {
            $description = null;
        }

        $details = $validatedData['details'] ?? null;
        $customFields = $validatedData['custom_fields'] ?? null;
        $seo = $validatedData['seo'] ?? null;
        if (is_array($seo) && empty(array_filter($seo))) { $seo = null; }


        return new CreateEcoProductDTO(
            companyId: Uuid::fromString($validatedData['company_id']),
            name: $validatedData['name'],
            description: $description,
            price: $validatedData['price'],
            sku: $validatedData['sku'],
            stock: $validatedData['stock'] ?? null,
            warehouseId: Uuid::fromString($validatedData['warehouse_id']),
            requiresShipping: $validatedData['requires_shipping'] ?? false,
            unlimitedQuantity: $validatedData['unlimited_quantity'] ?? false,
            isTaxable: $validatedData['is_taxable'] ?? true,
            priceIncludesVat: $validatedData['price_includes_vat'] ?? false,
            vatPercentage: $validatedData['vat_percentage'] ?? null,
            isVisible: $validatedData['is_visible'] ?? true,
            details: $details,
            customFields: $customFields,
            seo: $seo,
        );
    }
}
