<?php

namespace Modules\Shared\Currency\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Currency\Models\Currency;

class CurrencySeederTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $currencies = [
            ['en' => 'United States Dollar', 'ar' => 'دولار أمريكي', 'short_name' => 'USD'],
            ['en' => 'Euro', 'ar' => 'يورو', 'short_name' => 'EUR'],
            ['en' => 'British Pound', 'ar' => 'جنيه إسترليني', 'short_name' => 'GBP'],
            ['en' => 'Japanese Yen', 'ar' => 'ين ياباني', 'short_name' => 'JPY'],
            ['en' => 'Canadian Dollar', 'ar' => 'دولار كندي', 'short_name' => 'CAD'],
            ['en' => 'Australian Dollar', 'ar' => 'دولار أسترالي', 'short_name' => 'AUD'],
            ['en' => 'Swiss Franc', 'ar' => 'فرنك سويسري', 'short_name' => 'CHF'],
            ['en' => 'Chinese Yuan', 'ar' => 'يوان صيني', 'short_name' => 'CNY'],
            ['en' => 'Swedish Krona', 'ar' => 'كرونة سويدية', 'short_name' => 'SEK'],
            ['en' => 'New Zealand Dollar', 'ar' => 'دولار نيوزيلندي', 'short_name' => 'NZD'],
            ['en' => 'Mexican Peso', 'ar' => 'بيزو مكسيكي', 'short_name' => 'MXN'],
            ['en' => 'Singapore Dollar', 'ar' => 'دولار سنغافوري', 'short_name' => 'SGD'],
            ['en' => 'Hong Kong Dollar', 'ar' => 'دولار هونج كونج', 'short_name' => 'HKD'],
            ['en' => 'Norwegian Krone', 'ar' => 'كرونة نرويجية', 'short_name' => 'NOK'],
            ['en' => 'South Korean Won', 'ar' => 'وون كوري جنوبي', 'short_name' => 'KRW'],
            ['en' => 'Turkish Lira', 'ar' => 'ليرة تركية', 'short_name' => 'TRY'],
            ['en' => 'Russian Ruble', 'ar' => 'روبل روسي', 'short_name' => 'RUB'],
            ['en' => 'Indian Rupee', 'ar' => 'روبية هندية', 'short_name' => 'INR'],
            ['en' => 'Brazilian Real', 'ar' => 'ريال برازيلي', 'short_name' => 'BRL'],
            ['en' => 'South African Rand', 'ar' => 'راند جنوب أفريقي', 'short_name' => 'ZAR'],
            ['en' => 'Egyptian Pound', 'ar' => 'جنيه مصري', 'short_name' => 'EGP'],
            ['en' => 'Saudi Riyal', 'ar' => 'ريال سعودي', 'short_name' => 'SAR'],
            ['en' => 'United Arab Emirates Dirham', 'ar' => 'درهم إماراتي', 'short_name' => 'AED'],
            ['en' => 'Kuwaiti Dinar', 'ar' => 'دينار كويتي', 'short_name' => 'KWD'],
            ['en' => 'Qatari Riyal', 'ar' => 'ريال قطري', 'short_name' => 'QAR'],
            ['en' => 'Bahraini Dinar', 'ar' => 'دينار بحريني', 'short_name' => 'BHD'],
            ['en' => 'Omani Rial', 'ar' => 'ريال عماني', 'short_name' => 'OMR'],
            ['en' => 'Jordanian Dinar', 'ar' => 'دينار أردني', 'short_name' => 'JOD'],
            ['en' => 'Lebanese Pound', 'ar' => 'ليرة لبنانية', 'short_name' => 'LBP'],
            ['en' => 'Moroccan Dirham', 'ar' => 'درهم مغربي', 'short_name' => 'MAD'],
            ['en' => 'Tunisian Dinar', 'ar' => 'دينار تونسي', 'short_name' => 'TND'],
            ['en' => 'Algerian Dinar', 'ar' => 'دينار جزائري', 'short_name' => 'DZD'],
            ['en' => 'Iraqi Dinar', 'ar' => 'دينار عراقي', 'short_name' => 'IQD'],
            ['en' => 'Libyan Dinar', 'ar' => 'دينار ليبي', 'short_name' => 'LYD'],
            ['en' => 'Sudanese Pound', 'ar' => 'جنيه سوداني', 'short_name' => 'SDG'],
            ['en' => 'Syrian Pound', 'ar' => 'ليرة سورية', 'short_name' => 'SYP'],
            ['en' => 'Yemeni Rial', 'ar' => 'ريال يمني', 'short_name' => 'YER'],
            ['en' => 'Israeli New Shekel', 'ar' => 'شيكل إسرائيلي جديد', 'short_name' => 'ILS'],
            ['en' => 'Iranian Rial', 'ar' => 'ريال إيراني', 'short_name' => 'IRR'],
            ['en' => 'Pakistani Rupee', 'ar' => 'روبية باكستانية', 'short_name' => 'PKR'],
            ['en' => 'Bangladeshi Taka', 'ar' => 'تاكا بنغلاديشي', 'short_name' => 'BDT'],
            ['en' => 'Thai Baht', 'ar' => 'بات تايلاندي', 'short_name' => 'THB'],
            ['en' => 'Indonesian Rupiah', 'ar' => 'روبية إندونيسية', 'short_name' => 'IDR'],
            ['en' => 'Malaysian Ringgit', 'ar' => 'رينغيت ماليزي', 'short_name' => 'MYR'],
            ['en' => 'Philippine Peso', 'ar' => 'بيزو فلبيني', 'short_name' => 'PHP'],
            ['en' => 'Vietnamese Dong', 'ar' => 'دونغ فيتنامي', 'short_name' => 'VND'],
            ['en' => 'Nigerian Naira', 'ar' => 'نايرا نيجيري', 'short_name' => 'NGN'],
            ['en' => 'Ghanaian Cedi', 'ar' => 'سيدي غاني', 'short_name' => 'GHS'],
            ['en' => 'Kenyan Shilling', 'ar' => 'شلن كيني', 'short_name' => 'KES'],
            ['en' => 'Tanzanian Shilling', 'ar' => 'شلن تنزاني', 'short_name' => 'TZS'],
            ['en' => 'Ugandan Shilling', 'ar' => 'شلن أوغندي', 'short_name' => 'UGX'],
            ['en' => 'Ethiopian Birr', 'ar' => 'بير إثيوبي', 'short_name' => 'ETB'],
            ['en' => 'Argentine Peso', 'ar' => 'بيزو أرجنتيني', 'short_name' => 'ARS'],
            ['en' => 'Chilean Peso', 'ar' => 'بيزو تشيلي', 'short_name' => 'CLP'],
            ['en' => 'Colombian Peso', 'ar' => 'بيزو كولومبي', 'short_name' => 'COP'],
            ['en' => 'Peruvian Sol', 'ar' => 'سول بيروفي', 'short_name' => 'PEN'],
            ['en' => 'Uruguayan Peso', 'ar' => 'بيزو أوروغواي', 'short_name' => 'UYU'],
            ['en' => 'Venezuelan Bolívar', 'ar' => 'بوليفار فنزويلي', 'short_name' => 'VES'],
            ['en' => 'Polish Złoty', 'ar' => 'زلوتي بولندي', 'short_name' => 'PLN'],
            ['en' => 'Czech Koruna', 'ar' => 'كورونا تشيكية', 'short_name' => 'CZK'],
            ['en' => 'Hungarian Forint', 'ar' => 'فورنت مجري', 'short_name' => 'HUF'],
            ['en' => 'Romanian Leu', 'ar' => 'ليو روماني', 'short_name' => 'RON'],
            ['en' => 'Bulgarian Lev', 'ar' => 'ليف بلغاري', 'short_name' => 'BGN'],
            ['en' => 'Croatian Kuna', 'ar' => 'كونا كرواتية', 'short_name' => 'HRK'],
            ['en' => 'Danish Krone', 'ar' => 'كرونة دنماركية', 'short_name' => 'DKK'],
            ['en' => 'Icelandic Króna', 'ar' => 'كرونة آيسلندية', 'short_name' => 'ISK'],
            ['en' => 'Ukrainian Hryvnia', 'ar' => 'هريفنيا أوكرانية', 'short_name' => 'UAH'],
            ['en' => 'Kazakhstani Tenge', 'ar' => 'تينغ كازاخستاني', 'short_name' => 'KZT'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['short_name' => $currency['short_name']],
                ['name' => ['en' => $currency['en'], 'ar' => $currency['ar']]]
            );
        }
    }
}
