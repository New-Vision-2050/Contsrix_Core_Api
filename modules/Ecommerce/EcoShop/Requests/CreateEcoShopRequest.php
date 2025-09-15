<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoShop\DTO\CreateEcoShopDTO;

class CreateEcoShopRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20|regex:/^[+]?[0-9\s\-\(\)]+$/',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'snapchat_url' => 'nullable|url|max:255',
            'whatsapp_number' => 'nullable|string|max:20|regex:/^[+]?[0-9\s\-\(\)]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المتجر مطلوب',
            'name.max' => 'اسم المتجر يجب ألا يتجاوز 255 حرف',
            'company_id.required' => 'معرف الشركة مطلوب',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'phone.regex' => 'رقم الهاتف غير صحيح',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'website_url.url' => 'رابط الموقع غير صحيح',
            'facebook_url.url' => 'رابط فيسبوك غير صحيح',
            'instagram_url.url' => 'رابط انستجرام غير صحيح',
            'twitter_url.url' => 'رابط تويتر غير صحيح',
            'tiktok_url.url' => 'رابط تيك توك غير صحيح',
            'snapchat_url.url' => 'رابط سناب شات غير صحيح',
            'whatsapp_number.regex' => 'رقم الواتساب غير صحيح',
        ];
    }

    public function createCreateEcoShopDTO(): CreateEcoShopDTO
    {
        return new CreateEcoShopDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $this->get('name'),
            description: $this->get('description'),
            phone: $this->get('phone'),
            email: $this->get('email'),
            websiteUrl: $this->get('website_url'),
            facebookUrl: $this->get('facebook_url'),
            instagramUrl: $this->get('instagram_url'),
            twitterUrl: $this->get('twitter_url'),
            tiktokUrl: $this->get('tiktok_url'),
            snapchatUrl: $this->get('snapchat_url'),
            whatsappNumber: $this->get('whatsapp_number'),
        );
    }
}
