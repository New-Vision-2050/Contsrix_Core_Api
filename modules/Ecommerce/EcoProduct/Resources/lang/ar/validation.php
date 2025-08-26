<?php

return [
        'price_required' => 'سعر المنتج مطلوب.',
        'price_numeric' => 'يجب أن يكون سعر المنتج رقمًا.',
        'price_min' => 'يجب أن لا يقل سعر المنتج عن :min.',

        'sku_required' => 'رمز المنتج (SKU) مطلوب.',
        'sku_string' => 'يجب أن يكون رمز المنتج (SKU) نصًا.',
        'sku_max' => 'لا يجوز أن يتجاوز رمز المنتج (SKU) :max أحرف.',
        'sku_unique' => 'رمز المنتج (SKU) هذا مستخدم بالفعل.',

        'stock_integer' => 'يجب أن تكون كمية المخزون عددًا صحيحًا.',
        'stock_min' => 'يجب أن تكون كمية المخزون على الأقل :min.',

        'warehouse_id_required' => 'معرف المستودع مطلوب.',
        'warehouse_id_uuid' => 'يجب أن يكون معرف المستودع بتنسيق UUID صالح.',
        'warehouse_id_exists' => 'معرف المستودع المحدد غير موجود.',

        'requires_shipping_boolean' => 'يجب أن يكون حقل "يتطلب شحن" صحيحًا أو خاطئًا.',
        'unlimited_quantity_boolean' => 'يجب أن يكون حقل "كمية غير محدودة" صحيحًا أو خاطئًا.',
        'is_taxable_boolean' => 'يجب أن يكون حقل "قابل للضريبة" صحيحًا أو خاطئًا.',
        'price_includes_vat_boolean' => 'يجب أن يكون حقل "السعر يشمل ضريبة القيمة المضافة" صحيحًا أو خاطئًا.',
        'vat_percentage_numeric' => 'يجب أن تكون نسبة ضريبة القيمة المضافة رقمًا.',
        'vat_percentage_min' => 'يجب أن لا تقل نسبة ضريبة القيمة المضافة عن :min.',
        'vat_percentage_max' => 'لا يجوز أن تتجاوز نسبة ضريبة القيمة المضافة :max.',
        'is_visible_boolean' => 'يجب أن يكون حقل "مرئي" صحيحًا أو خاطئًا.',

        // Multilingual Name
        'name_required' => 'اسم المنتج مطلوب.',
        'name_array' => 'يجب أن يكون اسم المنتج مصفوفة من الترجمات.',
        'name_ar_required' => 'الاسم العربي مطلوب.',
        'name_ar_string' => 'يجب أن يكون الاسم العربي نصًا.',
        'name_ar_max' => 'لا يجوز أن يتجاوز الاسم العربي :max أحرف.',
        'name_en_string' => 'يجب أن يكون الاسم الإنجليزي نصًا.',
        'name_en_max' => 'لا يجوز أن يتجاوز الاسم الإنجليزي :max أحرف.',

        // Multilingual Description
        'description_array' => 'يجب أن يكون وصف المنتج مصفوفة من الترجمات.',
        'description_ar_required_with' => 'الوصف العربي مطلوب عند توفير وصف.',
        'description_ar_string' => 'يجب أن يكون الوصف العربي نصًا.',
        'description_ar_max' => 'لا يجوز أن يتجاوز الوصف العربي :max أحرف.',
        'description_en_string' => 'يجب أن يكون الوصف الإنجليزي نصًا.',
        'description_en_max' => 'لا يجوز أن يتجاوز الوصف الإنجليزي :max أحرف.',

        // Product Taxes
        'taxes_array' => 'يجب أن يكون حقل الضرائب مصفوفة.',
        'taxes_country_id_uuid' => 'يجب أن يكون معرف بلد الضريبة بتنسيق UUID صالح.',
        'taxes_country_id_exists' => 'معرف بلد ضريبة واحد أو أكثر غير موجود.',
        'taxes_tax_number_string' => 'يجب أن يكون كل رقم ضريبي نصًا.',
        'taxes_tax_number_max' => 'لا يجوز أن يتجاوز كل رقم ضريبي :max أحرف.',
        'taxes_tax_percentage_numeric' => 'يجب أن تكون كل نسبة ضريبة رقمًا.',
        'taxes_tax_percentage_min' => 'يجب أن لا تقل كل نسبة ضريبة عن :min.',
        'taxes_tax_percentage_max' => 'لا يجوز أن تتجاوز كل نسبة ضريبة :max.',
        'taxes_is_active_boolean' => 'يجب أن يكون حقل "نشط" لكل ضريبة صحيحًا أو خاطئًا.',

        // Product Details
        'details_array' => 'يجب أن يكون حقل تفاصيل المنتج مصفوفة.',
        'details_label_required_with' => 'كل تسمية تفصيلية مطلوبة عند توفير التفاصيل.',
        'details_label_string' => 'يجب أن تكون كل تسمية تفصيلية نصًا.',
        'details_label_max' => 'لا يجوز أن تتجاوز كل تسمية تفصيلية :max أحرف.',
        'details_value_required_with' => 'كل قيمة تفصيلية مطلوبة عند توفير التفاصيل.',
        'details_value_string' => 'يجب أن تكون كل قيمة تفصيلية نصًا.',
        'details_value_max' => 'لا يجوز أن تتجاوز كل قيمة تفصيلية :max أحرف.',

        // Product Custom Fields
        'custom_fields_array' => 'يجب أن يكون حقل الحقول المخصصة مصفوفة.',
        'custom_fields_field_name_required_with' => 'كل اسم حقل مخصص مطلوب عند توفير حقول مخصصة.',
        'custom_fields_field_name_string' => 'يجب أن يكون كل اسم حقل مخصص نصًا.',
        'custom_fields_field_name_max' => 'لا يجوز أن يتجاوز كل اسم حقل مخصص :max أحرف.',
        'custom_fields_field_value_required_with' => 'كل قيمة حقل مخصص مطلوبة عند توفير حقول مخصصة.',
        'custom_fields_field_value_string' => 'يجب أن تكون كل قيمة حقل مخصص نصًا.',
        'custom_fields_field_value_max' => 'لا يجوز أن تتجاوز كل قيمة حقل مخصص :max أحرف.',

        // Product SEO
        'seo_array' => 'يجب أن يكون حقل SEO مصفوفة.',
        'seo_meta_title_string' => 'يجب أن يكون العنوان التعريفي نصًا.',
        'seo_meta_title_max' => 'لا يجوز أن يتجاوز العنوان التعريفي :max أحرف.',
        'seo_meta_description_string' => 'يجب أن يكون الوصف التعريفي نصًا.',
        'seo_meta_description_max' => 'لا يجوز أن يتجاوز الوصف التعريفي :max أحرف.',
        'seo_meta_keywords_string' => 'يجب أن تكون الكلمات المفتاحية نصًا.',
        'seo_meta_keywords_max' => 'لا يجوز أن تتجاوز الكلمات المفتاحية :max أحرف.',
];
