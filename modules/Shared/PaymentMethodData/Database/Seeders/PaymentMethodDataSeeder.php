<?php

namespace Modules\Shared\PaymentMethodData\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\PaymentMethodData\Models\PaymentMethodData;
use Ramsey\Uuid\Uuid;
use Ranium\SeedOnce\Traits\SeedOnce;

class PaymentMethodDataSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'cash',
                'name' => ['ar' => 'نقداً', 'en' => 'Cash'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'card',
                'name' => ['ar' => 'بطاقة ائتمانية', 'en' => 'Credit Card'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'bank_transfer',
                'name' => ['ar' => 'تحويل بنكي', 'en' => 'Bank Transfer'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'digital_wallet',
                'name' => ['ar' => 'محفظة رقمية', 'en' => 'Digital Wallet'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'apple_pay',
                'name' => ['ar' => 'آبل باي', 'en' => 'Apple Pay'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'google_pay',
                'name' => ['ar' => 'جوجل باي', 'en' => 'Google Pay'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'paypal',
                'name' => ['ar' => 'باي بال', 'en' => 'PayPal'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'stc_pay',
                'name' => ['ar' => 'STC Pay', 'en' => 'STC Pay'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'mada',
                'name' => ['ar' => 'مدى', 'en' => 'Mada'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'visa',
                'name' => ['ar' => 'فيزا', 'en' => 'Visa'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'mastercard',
                'name' => ['ar' => 'ماستركارد', 'en' => 'Mastercard'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'tabby',
                'name' => ['ar' => 'تابي', 'en' => 'Tabby'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'tamara',
                'name' => ['ar' => 'تمارا', 'en' => 'Tamara'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethodData::updateOrCreate(
                ['type' => $method['type']],
                $method
            );
        }
    }
}
