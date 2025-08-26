<?php

return [
        'price_required' => 'The product price is required.',
        'price_numeric' => 'The product price must be a number.',
        'price_min' => 'The product price must be at least :min.',

        'sku_required' => 'The product SKU is required.',
        'sku_string' => 'The product SKU must be a string.',
        'sku_max' => 'The product SKU may not be greater than :max characters.',
        'sku_unique' => 'The provided SKU is already in use.',

        'stock_integer' => 'The stock quantity must be an integer.',
        'stock_min' => 'The stock quantity must be at least :min.',

        'warehouse_id_required' => 'The warehouse ID is required.',
        'warehouse_id_uuid' => 'The warehouse ID must be a valid UUID.',
        'warehouse_id_exists' => 'The selected warehouse ID does not exist.',

        'requires_shipping_boolean' => 'The "requires shipping" field must be true or false.',
        'unlimited_quantity_boolean' => 'The "unlimited quantity" field must be true or false.',
        'is_taxable_boolean' => 'The "is taxable" field must be true or false.',
        'price_includes_vat_boolean' => 'The "price includes VAT" field must be true or false.',
        'vat_percentage_numeric' => 'The VAT percentage must be a number.',
        'vat_percentage_min' => 'The VAT percentage must be at least :min.',
        'vat_percentage_max' => 'The VAT percentage may not be greater than :max.',
        'is_visible_boolean' => 'The "is visible" field must be true or false.',

        // Multilingual Name
        'name_required' => 'The product name is required.',
        'name_array' => 'The product name must be an array of translations.',
        'name_ar_required' => 'The Arabic name is required.',
        'name_ar_string' => 'The Arabic name must be a string.',
        'name_ar_max' => 'The Arabic name may not be greater than :max characters.',
        'name_en_string' => 'The English name must be a string.',
        'name_en_max' => 'The English name may not be greater than :max characters.',

        // Multilingual Description
        'description_array' => 'The product description must be an array of translations.',
        'description_ar_required_with' => 'The Arabic description is required when a description is provided.',
        'description_ar_string' => 'The Arabic description must be a string.',
        'description_ar_max' => 'The Arabic description may not be greater than :max characters.',
        'description_en_string' => 'The English description must be a string.',
        'description_en_max' => 'The English description may not be greater than :max characters.',

        // Product Taxes
        'taxes_array' => 'The taxes field must be an array.',
        'taxes_country_id_uuid' => 'Each tax country ID must be a valid UUID.',
        'taxes_country_id_exists' => 'One or more tax country IDs do not exist.',
        'taxes_tax_number_string' => 'Each tax number must be a string.',
        'taxes_tax_number_max' => 'Each tax number may not be greater than :max characters.',
        'taxes_tax_percentage_numeric' => 'Each tax percentage must be a number.',
        'taxes_tax_percentage_min' => 'Each tax percentage must be at least :min.',
        'taxes_tax_percentage_max' => 'Each tax percentage may not be greater than :max.',
        'taxes_is_active_boolean' => 'Each tax "is active" field must be true or false.',

        // Product Details
        'details_array' => 'The product details field must be an array.',
        'details_label_required_with' => 'Each detail label is required when details are provided.',
        'details_label_string' => 'Each detail label must be a string.',
        'details_label_max' => 'Each detail label may not be greater than :max characters.',
        'details_value_required_with' => 'Each detail value is required when details are provided.',
        'details_value_string' => 'Each detail value must be a string.',
        'details_value_max' => 'Each detail value may not be greater than :max characters.',

        // Product Custom Fields
        'custom_fields_array' => 'The custom fields field must be an array.',
        'custom_fields_field_name_required_with' => 'Each custom field name is required when custom fields are provided.',
        'custom_fields_field_name_string' => 'Each custom field name must be a string.',
        'custom_fields_field_name_max' => 'Each custom field name may not be greater than :max characters.',
        'custom_fields_field_value_required_with' => 'Each custom field value is required when custom fields are provided.',
        'custom_fields_field_value_string' => 'Each custom field value must be a string.',
        'custom_fields_field_value_max' => 'Each custom field value may not be greater than :max characters.',

        // Product SEO
        'seo_array' => 'The SEO field must be an array.',
        'seo_meta_title_string' => 'The meta title must be a string.',
        'seo_meta_title_max' => 'The meta title may not be greater than :max characters.',
        'seo_meta_description_string' => 'The meta description must be a string.',
        'seo_meta_description_max' => 'The meta description may not be greater than :max characters.',
        'seo_meta_keywords_string' => 'The meta keywords must be a string.',
        'seo_meta_keywords_max' => 'The meta keywords may not be greater than :max characters.',

];
