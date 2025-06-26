<?php

namespace Modules\Shared\Bank\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Bank\Models\Bank;
use Ranium\SeedOnce\Traits\SeedOnce;

class BanksOtherModulesSeederTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $banks = [

            // Tunisia Banks (country_id: 224)
            [
                'name' => ['en' => 'Société Tunisienne de Banque (STB)', 'ar' => 'الشركة التونسية للبنك'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Banque Nationale Agricole (BNA)', 'ar' => 'البنك الوطني الفلاحي'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'BH Bank', 'ar' => 'بنك الإسكان'],
                'country_id' => 224,
            ],

            // == Private Tunisian Banks (بنوك خاصة تونسية) ==
            [
                'name' => ['en' => 'Banque Internationale Arabe de Tunisie (BIAT)', 'ar' => 'بنك تونس العربي الدولي'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Amen Bank', 'ar' => 'آمان بنك'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Arab Tunisian Bank (ATB)', 'ar' => 'البنك العربي لتونس'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Banque de Tunisie (BT)', 'ar' => 'بنك تونس'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Union Bancaire pour le Commerce et l\'Industrie (UBCI)', 'ar' => 'الاتحاد البنكي للتجارة والصناعة'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Banque de Tunisie et des Emirats (BTE)', 'ar' => 'بنك تونس والإمارات'],
                'country_id' => 224,
            ],

            // == Islamic Banks (بنوك إسلامية) ==
            [
                'name' => ['en' => 'Banque Zitouna', 'ar' => 'بنك الزيتونة'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Al Baraka Bank Tunisia', 'ar' => 'بنك البركة تونس'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Wifak International Bank', 'ar' => 'بنك الوفاق الدولي'],
                'country_id' => 224,
            ],

            // == Foreign or Foreign-Majority Banks (بنوك أجنبية) ==
            [
                'name' => ['en' => 'Attijari bank Tunisie', 'ar' => 'التجاري بنك'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Union Internationale de Banques (UIB)', 'ar' => 'الاتحاد الدولي للبنوك'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'ABC Bank (Tunisia)', 'ar' => 'بنك ABC تونس'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Citibank N.A. (Tunisia Branch)', 'ar' => 'سيتي بنك (فرع تونس)'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'North Africa International Bank (NAIB)', 'ar' => 'مصرف شمال إفريقيا الدولي'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'Alubaf International Bank', 'ar' => 'مصرف الأوباف الدولي'],
                'country_id' => 224,
            ],

            // == Development & Investment Banks (بنوك التنمية والاستثمار) ==
            [
                'name' => ['en' => 'BFPME (Bank for Financing Small and Medium Enterprises)', 'ar' => 'بنك تمويل المؤسسات الصغرى والمتوسطة'],
                'country_id' => 224,
            ],
            [
                'name' => ['en' => 'BTS Bank (Tunisian Solidarity Bank)', 'ar' => 'البنك التونسي للتضامن'],
                'country_id' => 224,
            ],

            // Syria Banks (country_id: 215)
            // == Public (State-Owned) Banks ==
            [
                'name' => ['en' => 'Commercial Bank of Syria', 'ar' => 'المصرف التجاري السوري'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Real Estate Bank', 'ar' => 'المصرف العقاري'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Agricultural Cooperative Bank', 'ar' => 'المصرف الزراعي التعاوني'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Industrial Bank', 'ar' => 'المصرف الصناعي'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Popular Credit Bank', 'ar' => 'مصرف التسليف الشعبي'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Savings Bank', 'ar' => 'مصرف التوفير'],
                'country_id' => 215,
            ],

            // == Private Conventional Banks ==
            [
                'name' => ['en' => 'Bank Bemo Saudi Fransi (BBSF)', 'ar' => 'بنك بيمو السعودي الفرنسي'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Byblos Bank Syria', 'ar' => 'بنك بيبلوس - سورية'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Bank of Syria and Overseas', 'ar' => 'بنك سورية والمهجر'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Bank Audi Syria', 'ar' => 'بنك عوده - سورية'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Fransabank Syria', 'ar' => 'فرنسبنك - سورية'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'International Bank for Trade and Finance (IBTF)', 'ar' => 'المصرف الدولي للتجارة والتمويل'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Arab Bank - Syria', 'ar' => 'البنك العربي - سورية'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Qatar National Bank - Syria (QNB Syria)', 'ar' => 'بنك قطر الوطني - سورية'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Bank of Jordan - Syria', 'ar' => 'بنك الأردن - سورية'],
                'country_id' => 215,
            ],

            // == Private Islamic Banks ==
            [
                'name' => ['en' => 'Cham Bank', 'ar' => 'بنك الشام'],
                'country_id' => 215,
            ],
            [
                'name' => ['en' => 'Syria International Islamic Bank (SIIB)', 'ar' => 'بنك سورية الدولي الإسلامي'],
                'country_id' => 215,
            ],

            // Sudan Banks (country_id: 209)
           // == Major Commercial Banks ==
           [
            'name' => ['en' => 'Bank of Khartoum (BOK)', 'ar' => 'بنك الخرطوم'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Omdurman National Bank (ONB)', 'ar' => 'بنك أم درمان الوطني'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Faisal Islamic Bank of Sudan', 'ar' => 'بنك فيصل الإسلامي السوداني'],
            'country_id' => 209,
        ],

        // == Islamic Banks ==
        [
            'name' => ['en' => 'Al Baraka Bank Sudan', 'ar' => 'بنك البركة السوداني'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Tadamon Islamic Bank', 'ar' => 'بنك التضامن الإسلامي'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Farmer\'s Commercial Bank', 'ar' => 'مصرف المزارع التجاري'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Sudanese Islamic Bank', 'ar' => 'البنك الإسلامي السوداني'],
            'country_id' => 209,
        ],

        // == Other Private and Mixed Commercial Banks ==
        [
            'name' => ['en' => 'Sudanese French Bank', 'ar' => 'البنك السوداني الفرنسي'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'El Nilein Bank', 'ar' => 'بنك النيلين'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Savings and Social Development Bank', 'ar' => 'بنك الإدخار والتنمية الاجتماعية'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Saudi Sudanese Bank', 'ar' => 'البنك السعودي السوداني'],
            'country_id' => 209,
        ],
         [
            'name' => ['en' => 'Nile Bank', 'ar' => 'بنك النيل'],
            'country_id' => 209,
        ],
         [
            'name' => ['en' => 'Byblos Bank Africa', 'ar' => 'بنك بيبلوس أفريقيا'],
            'country_id' => 209,
        ],

        // == Specialized Government Banks ==
        [
            'name' => ['en' => 'Agricultural Bank of Sudan', 'ar' => 'البنك الزراعي السوداني'],
            'country_id' => 209,
        ],
        [
            'name' => ['en' => 'Animal Resources Bank', 'ar' => 'بنك الثروة الحيوانية'],
            'country_id' => 209,
        ],

            // Pakistan Banks (country_id: 167)
          // == Public (Government-Owned) Banks ==
          ['name' => ['en' => 'National Bank of Pakistan (NBP)', 'ar' => 'البنك الوطني الباكستاني'], 'country_id' => 167],
          ['name' => ['en' => 'The Bank of Punjab (BOP)', 'ar' => 'بنك البنجاب'], 'country_id' => 167],
          ['name' => ['en' => 'The Bank of Khyber (BOK)', 'ar' => 'بنك خيبر'], 'country_id' => 167],
          ['name' => ['en' => 'First Women Bank Ltd. (FWBL)', 'ar' => 'بنك المرأة الأول المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'SME Bank Ltd.', 'ar' => 'بنك المشاريع الصغيرة والمتوسطة'], 'country_id' => 167],

          // == Private Commercial Banks ==
          // The "Big 5"
          ['name' => ['en' => 'Habib Bank Limited (HBL)', 'ar' => 'حبيب بنك المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'MCB Bank Limited', 'ar' => 'بنك إم سي بي المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'United Bank Limited (UBL)', 'ar' => 'البنك المتحد المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Allied Bank Limited (ABL)', 'ar' => 'البنك المتحالف المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Bank Alfalah Limited', 'ar' => 'بنك الفلاح المحدود'], 'country_id' => 167],
          // Other Major Private Banks
          ['name' => ['en' => 'Bank Al-Habib Limited', 'ar' => 'بنك الحبيب المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Askari Bank Ltd.', 'ar' => 'بنك عسكري المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Faysal Bank Limited (FBL)', 'ar' => 'بنك فيصل المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'JS Bank Limited', 'ar' => 'بنك جي إس المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Soneri Bank Limited', 'ar' => 'بنك سونيري المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Summit Bank Limited', 'ar' => 'بنك القمة المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Silkbank Limited', 'ar' => 'بنك سيلك بنك المحدود'], 'country_id' => 167],

          // == Islamic Banks ==
          ['name' => ['en' => 'Meezan Bank Limited', 'ar' => 'بنك ميزان المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'BankIslami Pakistan Limited', 'ar' => 'بنك إسلامي باكستان المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Al Baraka Bank (Pakistan) Limited', 'ar' => 'بنك البركة (باكستان) المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Dubai Islamic Bank Pakistan Limited', 'ar' => 'بنك دبي الإسلامي باكستان المحدود'], 'country_id' => 167],

          // == Foreign Banks ==
          ['name' => ['en' => 'Standard Chartered Bank (Pakistan) Limited', 'ar' => 'بنك ستاندرد تشارترد (باكستان)'], 'country_id' => 167],
          ['name' => ['en' => 'Citibank N.A.', 'ar' => 'سيتي بنك'], 'country_id' => 167],
          ['name' => ['en' => 'Deutsche Bank A.G.', 'ar' => 'دويتشه بنك'], 'country_id' => 167],
          ['name' => ['en' => 'Industrial and Commercial Bank of China (ICBC)', 'ar' => 'البنك الصناعي والتجاري الصيني'], 'country_id' => 167],

          // == Specialized & Microfinance Banks ==
          // Specialized
          ['name' => ['en' => 'Zarai Taraqiati Bank Limited (ZTBL)', 'ar' => 'بنك التنمية الزراعية المحدود'], 'country_id' => 167],
          ['name' => ['en' => 'Industrial Development Bank of Pakistan (IDBP)', 'ar' => 'بنك التنمية الصناعية الباكستاني'], 'country_id' => 167],
          // Microfinance
          ['name' => ['en' => 'Telenor Microfinance Bank (EasyPaisa)', 'ar' => 'بنك تيلينور للتمويل الأصغر (ايزي بيسا)'], 'country_id' => 167],
          ['name' => ['en' => 'Mobilink Microfinance Bank (JazzCash)', 'ar' => 'بنك موبيلينك للتمويل الأصغر (جاز كاش)'], 'country_id' => 167],
          ['name' => ['en' => 'U Microfinance Bank Ltd (U Bank)', 'ar' => 'بنك يو للتمويل الأصغر'], 'country_id' => 167],
          ['name' => ['en' => 'Khushhali Microfinance Bank', 'ar' => 'بنك خوشحالي للتمويل الأصغر'], 'country_id' => 167],

            // Somalia Banks (country_id: 203)
            // == Regulatory Body ==
            [
                'name' => ['en' => 'Central Bank of Somalia (CBS)', 'ar' => 'البنك المركزي الصومالي'],
                'country_id' => 203,
            ],

            // == Licensed Private Commercial Banks ==
            [
                'name' => ['en' => 'Dahabshiil International Bank (DIB)', 'ar' => 'بنك دهب شيل الدولي'],
                'country_id' => 203,
            ],
            [
                'name' => ['en' => 'Premier Bank', 'ar' => 'بنك بريمير'],
                'country_id' => 203,
            ],
            [
                'name' => ['en' => 'International Bank of Somalia (IBS)', 'ar' => 'البنك الدولي الصومالي'],
                'country_id' => 203,
            ],
            [
                'name' => ['en' => 'Salaam Somali Bank (SSB)', 'ar' => 'بنك سلام الصومالي'],
                'country_id' => 203,
            ],
            [
                'name' => ['en' => 'Amal Bank', 'ar' => 'بنك الأمل'],
                'country_id' => 203,
            ],
            [
                'name' => ['en' => 'MyBank Limited', 'ar' => 'ماي بنك المحدود'],
                'country_id' => 203,
            ],
            [
                'name' => ['en' => 'Somali Commercial Bank', 'ar' => 'البنك التجاري الصومالي'],
                'country_id' => 203,
            ],
             [
                'name' => ['en' => 'Agro Africa Bank', 'ar' => 'بنك أغرو أفريقيا'],
                'country_id' => 203,
            ],

            // == Notable Regional Banks ==
            [
                'name' => ['en' => 'Dara-salam Bank (Somaliland)', 'ar' => 'بنك دار السلام (أرض الصومال)'],
                'country_id' => 203,
            ],
            [
                'name' => ['en' => 'Puntland Bank', 'ar' => 'بنك بونتلاند'],
                'country_id' => 203,
            ],

            // India Banks (country_id: 101)
            // == Public Sector Banks (PSBs) ==
            ['name' => ['en' => 'State Bank of India (SBI)', 'ar' => 'بنك الدولة الهندي'], 'country_id' => 101],
            ['name' => ['en' => 'Punjab National Bank (PNB)', 'ar' => 'بنك البنجاب الوطني'], 'country_id' => 101],
            ['name' => ['en' => 'Bank of Baroda (BoB)', 'ar' => 'بنك بارودا'], 'country_id' => 101],
            ['name' => ['en' => 'Canara Bank', 'ar' => 'بنك كانارا'], 'country_id' => 101],
            ['name' => ['en' => 'Union Bank of India', 'ar' => 'بنك الاتحاد الهندي'], 'country_id' => 101],
            ['name' => ['en' => 'Indian Bank', 'ar' => 'البنك الهندي'], 'country_id' => 101],
            ['name' => ['en' => 'Bank of India (BOI)', 'ar' => 'بنك الهند'], 'country_id' => 101],
            ['name' => ['en' => 'Central Bank of India', 'ar' => 'البنك المركزي الهندي'], 'country_id' => 101],

            // == Private Sector Banks ==
            ['name' => ['en' => 'HDFC Bank', 'ar' => 'بنك إتش دي إف سي'], 'country_id' => 101],
            ['name' => ['en' => 'ICICI Bank', 'ar' => 'بنك آي سي آي سي آي'], 'country_id' => 101],
            ['name' => ['en' => 'Axis Bank', 'ar' => 'بنك أكسيس'], 'country_id' => 101],
            ['name' => ['en' => 'Kotak Mahindra Bank', 'ar' => 'بنك كوتاك ماهيندرا'], 'country_id' => 101],
            ['name' => ['en' => 'IndusInd Bank', 'ar' => 'بنك إندوس إند'], 'country_id' => 101],
            ['name' => ['en' => 'Yes Bank', 'ar' => 'بنك يس'], 'country_id' => 101],
            ['name' => ['en' => 'Federal Bank', 'ar' => 'البنك الفيدرالي'], 'country_id' => 101],
            ['name' => ['en' => 'IDFC First Bank', 'ar' => 'بنك آي دي إف سي فيرست'], 'country_id' => 101],
            ['name' => ['en' => 'Bandhan Bank', 'ar' => 'بنك باندان'], 'country_id' => 101],
            ['name' => ['en' => 'RBL Bank', 'ar' => 'بنك آر بي إل'], 'country_id' => 101],

            // == Foreign Banks ==
            ['name' => ['en' => 'Citibank', 'ar' => 'سيتي بنك'], 'country_id' => 101],
            ['name' => ['en' => 'Standard Chartered Bank', 'ar' => 'بنك ستاندرد تشارترد'], 'country_id' => 101],
            ['name' => ['en' => 'HSBC', 'ar' => 'بنك إتش إس بي سي'], 'country_id' => 101],
            ['name' => ['en' => 'Deutsche Bank', 'ar' => 'دويتشه بنك'], 'country_id' => 101],
            ['name' => ['en' => 'DBS Bank', 'ar' => 'بنك دي بي إس'], 'country_id' => 101],
            ['name' => ['en' => 'Barclays Bank', 'ar' => 'بنك باركليز'], 'country_id' => 101],

            // == Other Bank Categories ==
            // Payments Banks
            ['name' => ['en' => 'Airtel Payments Bank', 'ar' => 'بنك إيرتل للمدفوعات'], 'country_id' => 101],
            ['name' => ['en' => 'Paytm Payments Bank', 'ar' => 'بنك بيتم للمدفوعات'], 'country_id' => 101],
            ['name' => ['en' => 'India Post Payments Bank', 'ar' => 'بنك بريد الهند للمدفوعات'], 'country_id' => 101],
            // Small Finance Banks
            ['name' => ['en' => 'AU Small Finance Bank', 'ar' => 'بنك إيه يو المالي الصغير'], 'country_id' => 101],
            ['name' => ['en' => 'Equitas Small Finance Bank', 'ar' => 'بنك إكويتاس المالي الصغير'], 'country_id' => 101],
            ['name' => ['en' => 'Ujjivan Small Finance Bank', 'ar' => 'بنك أوجيفان المالي الصغير'], 'country_id' => 101],

            // Bangladesh Banks (country_id: 19)
        // A comprehensive list of Bangladeshi banks, categorized as provided.
            // == State-Owned Commercial Banks (SOCBs) ==
            ['name' => ['en' => 'Sonali Bank PLC.', 'ar' => 'بنك سونالي'], 'country_id' => 19],
            ['name' => ['en' => 'Janata Bank PLC.', 'ar' => 'بنك جاناتا'], 'country_id' => 19],
            ['name' => ['en' => 'Agrani Bank PLC.', 'ar' => 'بنك أغراني'], 'country_id' => 19],
            ['name' => ['en' => 'Rupali Bank PLC.', 'ar' => 'بنك روبالي'], 'country_id' => 19],
            ['name' => ['en' => 'BASIC Bank Limited', 'ar' => 'بنك بيسيك المحدود'], 'country_id' => 19],

            // == Private Commercial Banks (PCBs) ==
            ['name' => ['en' => 'BRAC Bank PLC.', 'ar' => 'بنك براك'], 'country_id' => 19],
            ['name' => ['en' => 'Dutch-Bangla Bank PLC. (DBBL)', 'ar' => 'بنك داتش-بانغلا'], 'country_id' => 19],
            ['name' => ['en' => 'The City Bank PLC.', 'ar' => 'بنك المدينة'], 'country_id' => 19],
            ['name' => ['en' => 'Eastern Bank PLC. (EBL)', 'ar' => 'البنك الشرقي'], 'country_id' => 19],
            ['name' => ['en' => 'United Commercial Bank PLC. (UCB)', 'ar' => 'البنك التجاري المتحد'], 'country_id' => 19],
            ['name' => ['en' => 'Mutual Trust Bank PLC. (MTB)', 'ar' => 'بنك الثقة المتبادلة'], 'country_id' => 19],
            ['name' => ['en' => 'Prime Bank PLC.', 'ar' => 'بنك برايم'], 'country_id' => 19],
            ['name' => ['en' => 'Dhaka Bank PLC.', 'ar' => 'بنك دكا'], 'country_id' => 19],
            ['name' => ['en' => 'Trust Bank PLC.', 'ar' => 'بنك تراست'], 'country_id' => 19],

            // == Shariah-Based Islamic Banks ==
            ['name' => ['en' => 'Islami Bank Bangladesh PLC. (IBBL)', 'ar' => 'بنك بنغلاديش الإسلامي'], 'country_id' => 19],
            ['name' => ['en' => 'Al-Arafah Islami Bank PLC.', 'ar' => 'بنك عرفة الإسلامي'], 'country_id' => 19],
            ['name' => ['en' => 'EXIM Bank', 'ar' => 'بنك إكسيم'], 'country_id' => 19],
            ['name' => ['en' => 'Social Islami Bank PLC. (SIBL)', 'ar' => 'البنك الإسلامي الاجتماعي'], 'country_id' => 19],
            ['name' => ['en' => 'Shahjalal Islami Bank PLC.', 'ar' => 'بنك شاه جلال الإسلامي'], 'country_id' => 19],
            ['name' => ['en' => 'First Security Islami Bank PLC.', 'ar' => 'بنك الأمان الإسلامي الأول'], 'country_id' => 19],
            ['name' => ['en' => 'Union Bank PLC.', 'ar' => 'بنك الاتحاد'], 'country_id' => 19],
            
            // == Foreign Commercial Banks (FCBs) ==
            ['name' => ['en' => 'Standard Chartered Bangladesh', 'ar' => 'ستاندرد تشارترد بنغلاديش'], 'country_id' => 19],
            ['name' => ['en' => 'HSBC Bangladesh', 'ar' => 'إتش إس بي سي بنغلاديش'], 'country_id' => 19],
            ['name' => ['en' => 'Citibank, N.A. Bangladesh', 'ar' => 'سيتي بنك بنغلاديش'], 'country_id' => 19],
            ['name' => ['en' => 'Woori Bank', 'ar' => 'بنك ووري'], 'country_id' => 19],
            ['name' => ['en' => 'Bank Al-Falah Limited', 'ar' => 'بنك الفلاح المحدود'], 'country_id' => 19],

            // == Specialized Banks ==
            ['name' => ['en' => 'Bangladesh Krishi Bank (BKB)', 'ar' => 'بنك بنغلاديش الزراعي'], 'country_id' => 19],
            ['name' => ['en' => 'Rajshahi Krishi Unnayan Bank (RAKUB)', 'ar' => 'بنك راجشاهي للتنمية الزراعية'], 'country_id' => 19],
            ['name' => ['en' => 'Probashi Kallyan Bank', 'ar' => 'بنك رعاية المغتربين'], 'country_id' => 19],
        ];

        foreach ($banks as $bank) {
            Bank::create(
                [
                    'name' => [
                        'en' => $bank['name']['en'], // English name
                        'ar' => $bank['name']['ar'], // Arabic name
                    ],
                    'country_id' => $bank['country_id'],
                ]
            );
        }
    }
}