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
        // When updating, fields are usually nullable, meaning you only update what's provided.
        // 'sometimes' rule can be used if you want to apply rules only when the field is present.
        $productId = $this->route('id');

        return [
            'name' => ['nullable', 'array'], // Name is optional for update
            'name.ar' => ['required_with:name', 'string', 'max:255'], // Required if 'name' array is present
            'name.en' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.ar' => ['required_with:description', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],

            'price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:eco_products,sku,' . $productId], // Unique check for SKU, excluding current product
            'stock' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['nullable', 'uuid'],

            'requires_shipping' => ['nullable', 'boolean'],
            'unlimited_quantity' => ['nullable', 'boolean'],
            'is_taxable' => ['nullable', 'boolean'],
            'price_includes_vat' => ['nullable', 'boolean'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],

            // For nested relations, decide if you're updating existing or creating new ones
            // This usually requires more complex validation, often handled in separate requests/commands
            // For simplicity, if you were to allow updating existing records here
            // 'details.*.id' => ['sometimes', 'uuid'], // If updating existing details
            // 'details.*.label' => ['sometimes', 'string', 'max:255'],
            // 'details.*.value' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.array' => __('ecoproduct::validation.name_array'),
            'name.en.string' => __('ecoproduct::validation.name_en_string'),
            'name.ar.required_with' => __('ecoproduct::validation.name_ar_required_with'),
            'sku.unique' => __('ecoproduct::validation.sku_unique'),
        ];
    }

    public function createUpdateEcoProductCommand(): UpdateEcoProductCommand
    {
        $validatedData = $this->validated();

        $description = $validatedData['description'] ?? null;
        if (is_array($description) && empty(array_filter($description))) {
            $description = null;
        }

        return new UpdateEcoProductCommand(
            id: Uuid::fromString($this->route('id')),
            name: $validatedData['name'] ?? null,
            description: $description,
            price: $validatedData['price'] ?? null,
            sku: $validatedData['sku'] ?? null,
        );
    }
}
